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
        // 1) Load all products for the dropdown
        $products        = Product::orderBy('name')->get(['id', 'name']);
        $trainingResults = null;
        $realForecast    = null;

        if ($request->filled('product_id')) {
            $productId   = (int) $request->input('product_id');
            $product     = Product::find($productId);
            $productName = $product?->name ?: '—';

            // A) TRAINING & VALIDATION
            $firstRow = DB::table('detail_transactions as d')
                ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                ->where('d.product_id', $productId)
                ->selectRaw('MIN(DATE_TRUNC(\'month\', t.transaction_at)) AS first_month')
                ->first();

            $lastRow = DB::table('detail_transactions as d')
                ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                ->where('d.product_id', $productId)
                ->selectRaw('MAX(DATE_TRUNC(\'month\', t.transaction_at)) AS last_month')
                ->first();

            if (! $firstRow || ! $firstRow->first_month) {
                $trainingResults = [
                    'error'        => 'Produk belum memiliki transaksi sama sekali.',
                    'product_name' => $productName,
                ];
            } else {
                $firstReal  = Carbon::parse($firstRow->first_month)->startOfMonth();
                $lastReal   = Carbon::parse($lastRow->last_month)->startOfMonth();
                $today      = Carbon::now();

                // Last fully completed calendar year
                $lastFullYear   = $today->year - 1;
                $actualLastYear = min($lastReal->year, $lastFullYear);

                if ($actualLastYear < $firstReal->year) {
                    $trainingResults = [
                        'error'        => 'Belum ada satu tahun penuh data historis yang lengkap.',
                        'product_name' => $productName,
                    ];
                } else {
                    // Anchor Jan(firstReal.year) .. Dec(actualLastYear)
                    $startSeries = Carbon::create($firstReal->year, 1, 1);
                    $endSeries   = Carbon::create($actualLastYear, 12, 1);

                    // Pull & aggregate monthly qty
                    $rows = DB::table('detail_transactions as d')
                        ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                        ->where('d.product_id', $productId)
                        ->whereBetween('t.transaction_at', [
                            $startSeries->format('Y-m-01'),
                            $endSeries->copy()->endOfMonth()->format('Y-m-d'),
                        ])
                        ->selectRaw(
                            'EXTRACT(YEAR FROM t.transaction_at)::INT AS year, '
                            . 'EXTRACT(MONTH FROM t.transaction_at)::INT AS month, '
                            . 'SUM(d.qty) AS total_qty'
                        )
                        ->groupBy('year','month')
                        ->orderBy('year','asc')
                        ->orderBy('month','asc')
                        ->get();

                    // Build quick lookup
                    $lookup = [];
                    foreach ($rows as $r) {
                        $lookup[sprintf('%04d-%02d', $r->year, $r->month)] = (float) $r->total_qty;
                    }

                    // Zero‐fill all months in range
                    $cursor     = $startSeries->copy();
                    $zeroFilled = [];
                    $labelsAll  = [];

                    while ($cursor->lte($endSeries)) {
                        $key = $cursor->format('Y-m');
                        $zeroFilled[] = $lookup[$key] ?? 0.0;
                        $labelsAll[]  = $cursor->format('M Y');
                        $cursor->addMonth();
                    }

                    // Must have at least two full years
                    $yearsCount = $actualLastYear - $firstReal->year + 1;
                    if ($yearsCount < 2) {
                        $trainingResults = [
                            'error'        => 'Dibutuhkan minimal dua tahun penuh (Jan–Dec) untuk training & validation.',
                            'product_name' => $productName,
                        ];
                    } else {
                        // Split: train = all but final year; test = final calendar year
                        $monthsPerYear = 12;
                        $trainMonths   = ($yearsCount - 1) * $monthsPerYear;
                        $testMonths    = $monthsPerYear;

                        $trainSeries = array_slice($zeroFilled, 0, $trainMonths);
                        $testSeries  = array_slice($zeroFilled, $trainMonths, $testMonths);
                        $testLabels  = array_slice($labelsAll, $trainMonths, $testMonths);

                        // Grid‐search Holt–Winters
                        $alphaGrid = array_map(fn($a) => round($a,2), range(0.1,0.9,0.1));
                        $betaGrid  = [0.01, 0.05, 0.1, 0.2];
                        $gammaGrid = [0.01, 0.05, 0.1, 0.2];

                        $gridResult = HoltWinters::gridSearch(
                            $trainSeries,
                            $testSeries,
                            $alphaGrid,
                            $betaGrid,
                            $gammaGrid,
                            $monthsPerYear
                        );

                        // --- Build trainingResults with product_name & validationYear ---
                        $trainingResults = [
                            'product_name'   => $productName,
                            'validationYear' => $actualLastYear,
                            'trainSeries'    => $trainSeries,
                            'testSeries'     => $testSeries,
                            'testForecast'   => $gridResult['forecast'],
                            'testLabels'     => $testLabels,
                            'alpha'          => $gridResult['alpha'],
                            'beta'           => $gridResult['beta'],
                            'gamma'          => $gridResult['gamma'],
                            'mae'            => $gridResult['mae'],
                            'mape'           => $gridResult['mape'],
                        ];

                        // B) REAL FORECASTING (12 months beyond Dec(actualLastYear)), in-memory only
                        $hwFull       = HoltWinters::multiplicative(
                            $zeroFilled, 
                            $monthsPerYear,
                            $gridResult['alpha'],
                            $gridResult['beta'],
                            $gridResult['gamma']
                        );

                        $realForecast = [
                            'year'     => $actualLastYear + 1,
                            'forecast' => $hwFull['forecast'],
                        ];
                    }
                }
            }
        }

        return view('forecast.index', [
            'products'        => $products,
            'trainingResults' => $trainingResults,
            'realForecast'    => $realForecast,
        ]);
    }
}
