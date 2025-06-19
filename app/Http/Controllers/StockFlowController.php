<?php

namespace App\Http\Controllers;

use App\Exports\CashStockExport;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ReturItem;
use App\Models\DetailStokOpname;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class StockFlowController extends Controller
{
    public function index()
    {
        return view('report.stockIndex');
    }

    public function topSellingProducts(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $limit = $request->input('limit', 5);

        // 1. Total terjual per produk
        $sold = DetailTransaction::with('transaction')
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->whereBetween('transaction_at', [$dateRange['start'], $dateRange['end']])
            )
            ->get()
            ->groupBy('product_id')
            ->map(fn($items) => $items->sum('qty'));

        // 2. Urutkan dan ambil sesuai limit
        $sorted = $sold->sortDesc()
            ->take($limit === 'all' ? $sold->count() : (int) $limit);

        $products = Product::whereIn('id', $sorted->keys())
            ->pluck('name', 'id');

        $chartData = $sorted->map(fn($qtyTerjual, $productId) => [
            'name' => $products[$productId] ?? 'Unknown',
            'net_sold' => $qtyTerjual,
        ])->values();

        return response()->json($chartData);
    }

    public function productMovement(Request $request)
    {
        try {
            // 1. Ambil rentang tanggal
            $range = $request->input('range', 'all_time');
            $dateRange = $this->getDateRange($request);
            $productId = $request->input('product_id');
            if (!$productId) {
                throw new Exception("Parameter product_id wajib diisi");
            }

            $startDate = Carbon::parse($dateRange['start'])->startOfDay();
            $endDate = Carbon::parse($dateRange['end'])->endOfDay();

            // ------------------------------------------
            // 2. Hitung STOCK BEFORE (sebelum $startDate)
            // ------------------------------------------

            // 2.1. Transaksi (keluar → qty negatif)
            $sumTransBefore = DetailTransaction::where('product_id', $productId)
                ->whereHas('transaction', fn($q) => $q->where('transaction_at', '<', $startDate))
                ->sum('qty');
            $impactTransBefore = -1 * $sumTransBefore;

            // 2.2. Pembelian (masuk → qty positif), pakai entryDate
            $sumPurchBefore = ProductPurchase::where('product_id', $productId)
                ->whereHas('purchase', fn($q) => $q->where('entryDate', '<', $startDate))
                ->sum('qty');
            $impactPurchBefore = $sumPurchBefore;

            // 2.3. Retur (customer + supplier)
            $returItemsBefore = ReturItem::with('retur')
                ->where('product_id', $productId)
                ->whereHas('retur', fn($q) => $q->where('return_date', '<', $startDate))
                ->get();

            $impactReturBefore = $returItemsBefore->reduce(function ($carry, $item) {
                $isCustomer = $item->retur->return_type === 'customer';
                if ($isCustomer) {
                    // jika condition = 'good', stock bertambah; jika 'rusak', dianggap 0
                    return $carry + ($item->condition === 'good' ? $item->qty : 0);
                } else {
                    // Retur supplier → stok berkurang
                    return $carry - $item->qty;
                }
            }, 0);

            // 2.4. Stock Opname (difference bisa positif/negatif), pakai finish_at
            $sumOpnameBefore = DetailStokOpname::where('product_id', $productId)
                ->whereHas('schedule', fn($q) => $q->where('finish_at', '<', $startDate))
                ->sum('difference');
            $impactOpnameBefore = $sumOpnameBefore;

            $stockBefore = $impactTransBefore
                + $impactPurchBefore
                + $impactReturBefore
                + $impactOpnameBefore;

            // -------------------------------------------------
            // 3. Hitung STOCK CHANGE (selama $startDate – $endDate)
            // -------------------------------------------------

            // 3.1. Transaksi dalam rentang (qty negatif)
            $sumTransInRange = DetailTransaction::where('product_id', $productId)
                ->whereHas(
                    'transaction',
                    fn($q) =>
                    $q->whereBetween('transaction_at', [$startDate, $endDate])
                )
                ->sum('qty');
            $impactTransInRange = -1 * $sumTransInRange;

            // 3.2. Pembelian dalam rentang (qty positif), pakai entryDate
            $sumPurchInRange = ProductPurchase::where('product_id', $productId)
                ->whereHas(
                    'purchase',
                    fn($q) =>
                    $q->whereBetween('entryDate', [$startDate, $endDate])
                )
                ->sum('qty');
            $impactPurchInRange = $sumPurchInRange;

            // 3.3. Retur dalam rentang
            $returItemsInRange = ReturItem::with('retur')
                ->where('product_id', $productId)
                ->whereHas(
                    'retur',
                    fn($q) =>
                    $q->whereBetween('return_date', [$startDate, $endDate])
                )
                ->get();

            $impactReturInRange = $returItemsInRange->reduce(function ($carry, $item) {
                $isCustomer = $item->retur->return_type === 'customer';
                if ($isCustomer) {
                    return $carry + ($item->condition === 'good' ? $item->qty : 0);
                } else {
                    return $carry - $item->qty;
                }
            }, 0);

            // 3.4. Stock Opname dalam rentang (pakai finish_at)
            $sumOpnameInRange = DetailStokOpname::where('product_id', $productId)
                ->whereHas(
                    'schedule',
                    fn($q) =>
                    $q->whereBetween('finish_at', [$startDate, $endDate])
                )
                ->sum('difference');
            $impactOpnameInRange = $sumOpnameInRange;

            $stockChange = $impactTransInRange
                + $impactPurchInRange
                + $impactReturInRange
                + $impactOpnameInRange;

            $stockLast = $stockBefore + $stockChange;

            if ($range === 'all_time') {
                return response()->json([
                    'stock_before' => $stockBefore,
                    'stock_change' => $stockChange,
                    'stock_last' => $stockLast,
                    'movements' => [
                        [
                            // This single “movement” will become one stacked bar
                            'type' => 'Sepanjang Waktu',
                            'qty' => $stockChange,
                            'date' => 'Sepanjang Waktu',
                            'description' => 'Total pergerakan stok sejak awal'
                        ]
                    ],
                ]);
            }

            // -------------------------------------------------
            // 4. Ambil DETAIL MOVEMENTS (hanya aktivitas di rentang)
            // -------------------------------------------------
            // Kita akan kumpulkan semua “movement” ke dalam satu koleksi, 
            // sertakan kolom `dateTime` (Carbon) untuk sorting, 
            // lalu format `date` menjadi `d-m-Y H:i`.

            $allMovements = collect();

            // 4.1. Transaksi → keluar stok
            $transactions = DetailTransaction::with('transaction')
                ->where('product_id', $productId)
                ->whereHas(
                    'transaction',
                    fn($q) =>
                    $q->whereBetween('transaction_at', [$startDate, $endDate])
                )
                ->get()
                ->map(fn($item) => [
                    'type' => 'Penjualan',
                    'qty' => -$item->qty,
                    // simpan Carbon instan agar mudah sortir
                    'dateTime' => Carbon::parse($item->transaction->transaction_at),
                    // untuk ditampilkan ke front‐end
                    'date' => Carbon::parse($item->transaction->transaction_at)->format('d-m-Y H:i'),
                    'description' => 'Penjualan #' . $item->transaction->id,
                ]);
            $allMovements = $allMovements->merge($transactions);

            // 4.2. Pembelian → masuk stok, pakai entryDate
            $purchases = ProductPurchase::with('purchase')
                ->where('product_id', $productId)
                ->whereHas(
                    'purchase',
                    fn($q) =>
                    $q->whereBetween('entryDate', [$startDate, $endDate])
                )
                ->get()
                ->map(fn($item) => [
                    'type' => 'Pembelian',
                    'qty' => $item->qty,
                    'dateTime' => Carbon::parse($item->purchase->entryDate),
                    'date' => Carbon::parse($item->purchase->entryDate)->format('d-m-Y H:i'),
                    'description' => 'Pembelian #' . $item->purchase->id,
                ]);
            $allMovements = $allMovements->merge($purchases);

            // 4.3. Retur (customer + supplier)
            $returs = $returItemsInRange->map(function ($item) {
                $dt = Carbon::parse($item->retur->return_date);
                $formatted = $dt->format('d-m-Y H:i');
                $isCustomer = $item->retur->return_type === 'customer';

                if ($isCustomer) {
                    if ($item->condition === 'good') {
                        return [
                            'type' => 'Retur Customer (Baik)',
                            'qty' => $item->qty,
                            'dateTime' => $dt,
                            'date' => $formatted,
                            'description' => 'Retur #' . $item->retur->id . ' (Baik)',
                        ];
                    }
                    // Retur customer rusak dianggap qty = 0
                    return [
                        'type' => 'Retur Customer (Rusak)',
                        'qty' => 0,
                        'dateTime' => $dt,
                        'date' => $formatted,
                        'description' => 'Retur #' . $item->retur->id . ' (Rusak)',
                    ];
                } else {
                    return [
                        'type' => 'Retur Supplier',
                        'qty' => -$item->qty,
                        'dateTime' => $dt,
                        'date' => $formatted,
                        'description' => 'Retur Supplier #' . $item->retur->id,
                    ];
                }
            });
            $allMovements = $allMovements->merge($returs);

            // 4.4. Stock Opname → pakai finish_at
            $opnames = DetailStokOpname::with('schedule')
                ->where('product_id', $productId)
                ->whereHas(
                    'schedule',
                    fn($q) =>
                    $q->whereBetween('finish_at', [$startDate, $endDate])
                )
                ->get()
                ->map(fn($item) => [
                    'type' => 'Stok Opname',
                    'qty' => $item->difference,
                    'dateTime' => Carbon::parse($item->schedule->finish_at),
                    'date' => Carbon::parse($item->schedule->finish_at)->format('d-m-Y H:i'),
                    'description' => 'Opname #' . $item->schedule->id,
                ]);
            $allMovements = $allMovements->merge($opnames);

            // Urutkan berdasarkan dateTime, lalu reset index
            $movements = $allMovements
                ->sortBy('dateTime')
                ->values()
                // hapus kolom dateTime sebelum kirim ke front‐end
                ->map(fn($row) => [
                    'type' => $row['type'],
                    'qty' => $row['qty'],
                    'date' => $row['date'],
                    'description' => $row['description'],
                ]);

            // -------------------------------------------------
            // 5. Kirim JSON RESPONSE
            // -------------------------------------------------
            return response()->json([
                'stock_before' => $stockBefore,
                'stock_change' => $stockChange,
                'stock_last' => $stockLast,
                'movements' => $movements,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengembalikan rentang tanggal berdasarkan parameter range/custom.
     */
    private function getDateRange(Request $request): array
    {
        $type = $request->input('range', 'all_time');
        $customStart = $request->input('start');
        $customEnd = $request->input('end');

        return match ($type) {
            'today' => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'this_week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'this_year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            'custom' => [
                'start' => Carbon::parse($customStart)->startOfDay(),
                'end' => Carbon::parse($customEnd)->endOfDay(),
            ],
            default => ['start' => Carbon::createFromDate(2000, 1, 1), 'end' => now()],
        };
    }

    public function export(Request $request)
    {
        // 1. Validate input
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        // 2. Parse to Carbon (with startOfDay/endOfDay)
        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();

        // 3. Build filename using d-m-Y
        $filename = sprintf(
            'laporan_stok_%s_sampai_%s.xlsx',
            $start->format('d-m-Y'),
            $end->format('d-m-Y')
        );

        // 4. Download Excel (two sheets)
        return Excel::download(new CashStockExport($start, $end), $filename);
    }
}
