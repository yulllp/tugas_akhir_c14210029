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

        $currentYear = Carbon::now()->year;
        $selectedYm = $request->input('suggestion_month');
        if (
            ! $selectedYm
            || (int) substr($selectedYm, 0, 4) !== $currentYear
        ) {
            // default = next month if in same year, else current month
            $next = Carbon::now()->addMonth();
            $selectedYm = ($next->year === $currentYear)
                ? $next->format('Y-m')
                : Carbon::now()->format('Y-m');
        }
        [$selYear, $selMonth] = explode('-', $selectedYm);

        // Prepare suggestions: loop *all* products
        $suggestions = Product::orderBy('name')->get()->map(function ($product) use ($selYear, $selMonth) {
            // ── 1) Build the historical series for this product ──
            $createdAt = Carbon::parse($product->created_at)->startOfDay();
            $firstYear = (int) $createdAt->year;
            if ($createdAt->month > 1 || $createdAt->day > 1) {
                $firstYear++;
            }
            $lastFullYear = Carbon::now()->year - 1;

            // get last year with at least one sale
            $lastRow = DB::table('detail_transactions as d')
                ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                ->where('d.product_id', $product->id)
                ->selectRaw('MAX(EXTRACT(YEAR FROM t.transaction_at))::INT AS last_year')
                ->first();
            $actualLastYear = min($lastRow->last_year ?? 0, $lastFullYear);

            // if not enough data, skip forecasting → all zeros
            if (!$lastRow->last_year || ($actualLastYear - $firstYear + 1) < 2) {
                $forecastSeries = array_fill(0, 12, 0);
            } else {
                // zero‐fill Jan–Dec for each full year
                $start = Carbon::create($firstYear, 1, 1);
                $end   = Carbon::create($actualLastYear, 12, 1);
                $lookup = [];
                $rows = DB::table('detail_transactions as d')
                    ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                    ->where('d.product_id', $product->id)
                    ->whereYear('t.transaction_at', '>=', $firstYear)
                    ->whereYear('t.transaction_at', '<=', $actualLastYear)
                    ->selectRaw(
                        'EXTRACT(YEAR FROM t.transaction_at)::INT AS year,'
                            . 'EXTRACT(MONTH FROM t.transaction_at)::INT AS month,'
                            . 'SUM(d.qty) AS total_qty'
                    )
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'asc')
                    ->orderBy('month', 'asc')
                    ->get();
                foreach ($rows as $r) {
                    $key = sprintf('%04d-%02d', $r->year, $r->month);
                    $lookup[$key] = (float)$r->total_qty;
                }
                $cursor = $start->copy();
                $series = [];
                while ($cursor->lte($end)) {
                    $ym = $cursor->format('Y-m');
                    $series[] = $lookup[$ym] ?? 0.0;
                    $cursor->addMonth();
                }

                // split train/test
                $monthsPerYear = 12;
                $yearsCount   = $actualLastYear - $firstYear + 1;
                $trainCount   = ($yearsCount - 1) * $monthsPerYear;
                $trainSeries  = array_slice($series, 0, $trainCount);
                $testSeries   = array_slice($series, $trainCount, $monthsPerYear);

                // grid search for (α,β,γ)
                $alphaGrid = range(0.1, 0.9, 0.1);
                $betaGrid  = range(0.05, 0.9, 0.05);
                $gammaGrid = range(0.05, 0.9, 0.05);

                $grid = HoltWinters::gridSearch(
                    $trainSeries,
                    $testSeries,
                    array_map(fn($v) => round($v, 2), $alphaGrid),
                    array_map(fn($v) => round($v, 2), $betaGrid),
                    array_map(fn($v) => round($v, 2), $gammaGrid),
                    $monthsPerYear
                );

                // full‐series forecast
                $hw = HoltWinters::multiplicative(
                    $series,
                    $monthsPerYear,
                    $grid['alpha'],
                    $grid['beta'],
                    $grid['gamma']
                );
                $forecastSeries = $hw['forecast']; // 12‐month ahead
            }

            // ── 2) Build a lookup for this product’s forecast ──
            $forecastLookup = [];
            foreach ($forecastSeries as $idx => $qty) {
                $yr = ($actualLastYear ?? Carbon::now()->year) + 1;
                $ym = $yr . '-' . str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
                $forecastLookup[$ym] = (int) round($qty);
            }

            // ── 3) Determine “previous month” and actual vs predicted ──
            $lookupYm = Carbon::create($selYear, $selMonth, 1)->subMonth()->format('Y-m');
            $actual   = (int) DB::table('detail_transactions as d')
                ->join('transactions as t', 't.id', '=', 'd.transaction_id')
                ->where('d.product_id', $product->id)
                ->whereRaw("TO_CHAR(t.transaction_at,'YYYY-MM') = ?", [$lookupYm])
                ->sum('d.qty');
            $isActual = Carbon::parse($lookupYm)
                ->lt(Carbon::now()->startOfMonth());
            $displaySales = $isActual
                ? $actual
                : ($forecastLookup[$lookupYm] ?? 0);

            // ── 4) Predicted for the *selected* month ──
            $predKey = "{$selYear}-" . str_pad($selMonth, 2, '0', STR_PAD_LEFT);
            $predQty = $forecastLookup[$predKey] ?? 0;

            // ── 5) Build suggestion row ──
            $stock = $product->stock;
            $diff  = $displaySales - $stock;

            $hasForecast = ! empty(array_filter($forecastSeries)); 

            return [
                'name'           => $product->name,
                'sales'          => $displaySales,
                'is_actual'      => $isActual,
                'predicted'      => $predQty,
                'has_forecast'   => $hasForecast,
                'current_stock'  => $stock,
                'suggestion_qty' => abs($diff),
                'suggestion_up'  => $diff > 0,
            ];
        });

        $type = $request->input('suggestion_type', 'all'); // default = all
        if (in_array($type, ['up', 'down'])) {
            $suggestions = $suggestions->filter(
                fn($row) => $type === 'up'
                    ? $row['suggestion_up']
                    : ! $row['suggestion_up']
            );
        }

        return view('forecast.index', [
            'products'         => $products,
            'trainingResults'  => $trainingResults,
            'realForecast'     => $realForecast,
            // pass month dropdown and suggestions
            'suggestionMonth'  => $selectedYm,
            'suggestions'      => $suggestions,
            'suggestionType' => $type,
        ]);
    }
}
