<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\DetailTransaction;
use App\Models\ProductPurchase;
use App\Models\ReturItem;
use App\Models\DetailStokOpname;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Carbon\Carbon;

class SectionSummarySheet implements FromArray, WithTitle, WithColumnWidths
{
    protected $start;
    protected $end;

    public function __construct(Carbon $start, Carbon $end)
    {
        // normalize to full days
        $this->start = $start->startOfDay();
        $this->end = $end->endOfDay();
    }

    public function array(): array
    {
        $rows = [];

        // 1) Title row
        $rows[] = ["Laporan Stok {$this->start->format('d-m-Y')} s/d {$this->end->format('d-m-Y')}"];

        // 2) Blank row
        $rows[] = [];

        // 3) Headings
        $rows[] = ['Nama Produk', 'Stok Awal', 'Stok Masuk', 'Stok Keluar', 'Stok Akhir'];

        // 4) Data rows
        $products = Product::where('created_at', '<=', $this->end)->get();

        foreach ($products as $p) {
            //
            // --- calculate stok awal (all movements before $this->start) ---
            //
            $stockStart = 0;

            // 1. Sales before → keluar
            $sumSalesBefore = (int) DetailTransaction::where('product_id', $p->id)
                ->whereHas('transaction', fn($q) => $q->where('transaction_at', '<', $this->start))
                ->sum('qty');
            $stockStart -= $sumSalesBefore;

            // 2. Purchases before → masuk
            $sumPurchasesBefore = (int) ProductPurchase::where('product_id', $p->id)
                ->whereHas('purchase', fn($q) => $q->where('entryDate', '<', $this->start))
                ->sum('qty');
            $stockStart += $sumPurchasesBefore;

            // 3. Customer returns (good) before → masuk
            $sumReturCustGoodBefore = (int) ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q
                    ->where('return_date', '<', $this->start)
                    ->where('return_type', 'customer'))
                ->where('condition', 'good')
                ->sum('qty');
            $stockStart += $sumReturCustGoodBefore;

            // 4. Supplier returns before → keluar
            $sumReturSuppBefore = (int) ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q
                    ->where('return_date', '<', $this->start)
                    ->where('return_type', 'supplier'))
                ->sum('qty');
            $stockStart -= $sumReturSuppBefore;

            // 5. Stock‑opname before → difference (can be + or –)
            $sumOpnameBefore = (int) DetailStokOpname::where('product_id', $p->id)
                ->whereHas('schedule', fn($q) => $q->where('finish_at', '<', $this->start))
                ->sum('difference');
            $stockStart += $sumOpnameBefore;

            //
            // --- calculate movements within [$this->start .. $this->end] ---
            //
            $stokMasuk = 0;
            $stokKeluar = 0;

            // a) Sales in range → keluar
            $stokKeluar += (int) DetailTransaction::where('product_id', $p->id)
                ->whereHas('transaction', fn($q) => $q->whereBetween('transaction_at', [$this->start, $this->end]))
                ->sum('qty');

            // b) Purchases in range → masuk
            $stokMasuk += (int) ProductPurchase::where('product_id', $p->id)
                ->whereHas('purchase', fn($q) => $q->whereBetween('entryDate', [$this->start, $this->end]))
                ->sum('qty');

            // c) Customer returns in range → masuk
            $stokMasuk += (int) ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q
                    ->whereBetween('return_date', [$this->start, $this->end])
                    ->where('return_type', 'customer'))
                ->where('condition', 'good')
                ->sum('qty');

            // d) Supplier returns in range → keluar
            $stokKeluar += (int) ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q
                    ->whereBetween('return_date', [$this->start, $this->end])
                    ->where('return_type', 'supplier'))
                ->sum('qty');

            // e) Stock-opname in range
            $sumOpnamePos = (int) DetailStokOpname::where('product_id', $p->id)
                ->whereHas('schedule', fn($q) => $q->whereBetween('finish_at', [$this->start, $this->end]))
                ->where('difference', '>', 0)
                ->sum('difference');
            $sumOpnameNeg = (int) DetailStokOpname::where('product_id', $p->id)
                ->whereHas('schedule', fn($q) => $q->whereBetween('finish_at', [$this->start, $this->end]))
                ->where('difference', '<', 0)
                ->sum('difference');

            $stokMasuk += $sumOpnamePos;
            $stokKeluar += abs($sumOpnameNeg);

            //
            // --- final stok akhir ---
            //
            $stockEnd = $stockStart + $stokMasuk - $stokKeluar;

            $rows[] = [
                (string) $stockStart,
                (string) $stokMasuk,
                (string) $stokKeluar,
                (string) $stockEnd,
            ];

        }

        // dd($rows);

        return $rows;
    }

    public function title(): string
    {
        return 'Ringkasan Stok';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }
}