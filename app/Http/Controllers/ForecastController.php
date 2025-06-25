<?php
// app/Http/Controllers/ForecastController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\HoltWinters;
use App\Models\Product;
use Carbon\Carbon;

class ForecastController extends Controller
{
    public function index(Request $request)
    {
        //Load all products for the dropdown
        $products = Product::orderBy('name')->get(['id', 'name']);
        $trainingResults = null;
        $realForecast = null;

        if ($request->filled('product_id')) {
            $productId = (int) $request->input('product_id');
            $product = Product::find($productId);
            $productName = $product?->name ?: '—';

            if (!$product) {
                $trainingResults = [
                    'error' => 'Produk tidak ditemukan.',
                    'product_name' => $productName,
                ];
            } else {
                //Anchor on product creation date
                $createdAt = Carbon::parse($product->created_at)->startOfDay();
                $firstYear = (int) $createdAt->year;
                // if created mid-year, skip that partial year:
                if ($createdAt->month > 1 || $createdAt->day > 1) {
                    $firstYear += 1;
                }

                $today = Carbon::now();
                $lastFullYear = $today->year - 1;

                // figure out last calendar year we can include
                // but we also must have at least one transaction in that year for validation later
                $lastRow = DB::table('detail_transactions as d')
                    ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                    ->where('d.product_id', $productId)
                    ->selectRaw('MAX(EXTRACT(YEAR FROM t.transaction_at))::INT AS last_year')
                    ->first();

                $actualLastYear = min($lastRow->last_year ?? 0, $lastFullYear);

                // no transactions at all?
                if (!$lastRow->last_year) {
                    $trainingResults = [
                        'error' => 'Produk belum memiliki penjualan sama sekali.',
                        'product_name' => $productName,
                    ];
                }
                // not enough full years
                elseif (($actualLastYear - $firstYear + 1) < 2) {
                    $trainingResults = [
                        'error' => 'Dibutuhkan minimal dua tahun penuh (Jan–Dec) untuk training & validation.',
                        'product_name' => $productName,
                    ];
                } else {
                    // define series Jan–Dec for each full year
                    $startSeries = Carbon::create($firstYear, 1, 1);
                    $endSeries = Carbon::create($actualLastYear, 12, 1);

                    // Pull & aggregate monthly qty
                    $rows = DB::table('detail_transactions as d')
                        ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                        ->where('d.product_id', $productId)
                        ->whereYear('t.transaction_at', '>=', $firstYear)
                        ->whereYear('t.transaction_at', '<=', $actualLastYear)
                        ->selectRaw(
                            'EXTRACT(YEAR FROM t.transaction_at)::INT AS year, '
                            . 'EXTRACT(MONTH FROM t.transaction_at)::INT AS month, '
                            . 'SUM(d.qty) AS total_qty'
                        )
                        ->groupBy('year', 'month')
                        ->orderBy('year', 'asc')
                        ->orderBy('month', 'asc')
                        ->get();

                    // Build lookup
                    $lookup = [];
                    foreach ($rows as $r) {
                        $lookup[sprintf('%04d-%02d', $r->year, $r->month)] = (float) $r->total_qty;
                    }

                    // Zero‐fill all months in range
                    $cursor = $startSeries->copy();
                    $zeroFilled = [];
                    $labelsAll = [];

                    while ($cursor->lte($endSeries)) {
                        $key = $cursor->format('Y-m');
                        $zeroFilled[] = $lookup[$key] ?? 0.0;
                        $labelsAll[] = $cursor->format('M Y');
                        $cursor->addMonth();
                    }

                    // Split: train = all but final year; test = final calendar year
                    $monthsPerYear = 12;
                    $yearsCount = $actualLastYear - $firstYear + 1;
                    $trainMonths = ($yearsCount - 1) * $monthsPerYear;
                    $testMonths = $monthsPerYear;

                    $trainSeries = array_slice($zeroFilled, 0, $trainMonths);
                    $testSeries = array_slice($zeroFilled, $trainMonths, $testMonths);
                    $testLabels = array_slice($labelsAll, $trainMonths, $testMonths);

                    // Grid‐search Holt–Winters
                    $alphaGrid = array_map(fn($a) => round($a, 2), range(0.1, 0.9, 0.1));
                    $betaGrid = array_map(fn($b) => round($b, 2), range(0.05, 0.90, 0.05));
                    $gammaGrid = array_map(fn($g) => round($g, 2), range(0.05, 0.90, 0.05));

                    $gridResult = HoltWinters::gridSearch(
                        $trainSeries,
                        $testSeries,
                        $alphaGrid,
                        $betaGrid,
                        $gammaGrid,
                        $monthsPerYear
                    );

                    $trainingResults = [
                        'product_name' => $productName,
                        'validationYear' => $actualLastYear,
                        'trainSeries' => $trainSeries,
                        'testSeries' => $testSeries,
                        'testForecast' => $gridResult['forecast'],
                        'testLabels' => $testLabels,
                        'alpha' => $gridResult['alpha'],
                        'beta' => $gridResult['beta'],
                        'gamma' => $gridResult['gamma'],
                        'mae' => $gridResult['mae'],
                        'mape' => $gridResult['mape'],
                    ];

                    // B) REAL FORECASTING (12 months beyond Dec(actualLastYear)), in-memory only
                    $hwFull = HoltWinters::multiplicative(
                        $zeroFilled,
                        $monthsPerYear,
                        $gridResult['alpha'],
                        $gridResult['beta'],
                        $gridResult['gamma']
                    );

                    $realForecast = [
                        'year' => $actualLastYear + 1,
                        'forecast' => $hwFull['forecast'],
                    ];
                }
            }
        }

        return view('forecast.index', [
            'products' => $products,
            'trainingResults' => $trainingResults,
            'realForecast' => $realForecast,
        ]);
    }
}
