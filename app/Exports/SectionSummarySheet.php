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
        $this->end   = $end->endOfDay();
    }

    public function array(): array
    {
        $rows = [];

        // 1) Title row
        $rows[] = ["Laporan Stok {$this->start->format('d-m-Y')} s/d {$this->end->format('d-m-Y')}"];

        // 2) Blank row
        $rows[] = [];

        // 3) Headings
        $rows[] = ['Product Name', 'Stok Awal', 'Stok Masuk', 'Stok Keluar', 'Stok Akhir'];

        // 4) Data rows
        //    — only products created on or before the end date
        $products = Product::where('created_at', '<=', $this->end)->get();

        foreach ($products as $p) {
            // --- STOCK START (all < start) ---
            // sales (qty out)
            $sumSalesBefore = DetailTransaction::where('product_id', $p->id)
                ->whereHas('transaction', fn($q) => $q->where('transaction_at', '<', $this->start))
                ->sum('qty');
            $stockStart = - $sumSalesBefore;

            // purchases (qty in)
            $stockStart += ProductPurchase::where('product_id', $p->id)
                ->whereHas('purchase', fn($q) => $q->where('entryDate', '<', $this->start))
                ->sum('qty');

            // returns before
            $sumReturCustGoodBefore = ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q->where('return_date', '<', $this->start)
                                                  ->where('return_type', 'customer'))
                ->where('condition', 'good')
                ->sum('qty');
            $sumReturSuppBefore    = ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q->where('return_date', '<', $this->start)
                                                  ->where('return_type', 'supplier'))
                ->sum('qty');
            // customer good → +, supplier → - 
            $stockStart += $sumReturCustGoodBefore - $sumReturSuppBefore;

            // stock-opname before
            $stockStart += DetailStokOpname::where('product_id', $p->id)
                ->whereHas('schedule', fn($q) => $q->where('finish_at', '<', $this->start))
                ->sum('difference');

            // --- CHANGES (between start..end) ---
            $stokMasuk  = 0;
            $stokKeluar = 0;

            // sales in range → keluar
            $stokKeluar += DetailTransaction::where('product_id', $p->id)
                ->whereHas('transaction', fn($q) => $q->whereBetween('transaction_at', [$this->start, $this->end]))
                ->sum('qty');

            // purchases in range → masuk
            $stokMasuk += ProductPurchase::where('product_id', $p->id)
                ->whereHas('purchase', fn($q) => $q->whereBetween('entryDate', [$this->start, $this->end]))
                ->sum('qty');

            // returns in range
            $sumReturCustGood = ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q->whereBetween('return_date', [$this->start, $this->end])
                                                  ->where('return_type', 'customer'))
                ->where('condition', 'good')
                ->sum('qty');
            $sumReturSupp    = ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q->whereBetween('return_date', [$this->start, $this->end])
                                                  ->where('return_type', 'supplier'))
                ->sum('qty');
            $stokMasuk  += $sumReturCustGood;
            $stokKeluar += $sumReturSupp;

            // stock-opname in range
            $sumOpnamePos = DetailStokOpname::where('product_id', $p->id)
                ->whereHas('schedule', fn($q) => $q->whereBetween('finish_at', [$this->start, $this->end]))
                ->where('difference', '>', 0)
                ->sum('difference');
            $sumOpnameNeg = DetailStokOpname::where('product_id', $p->id)
                ->whereHas('schedule', fn($q) => $q->whereBetween('finish_at', [$this->start, $this->end]))
                ->where('difference', '<', 0)
                ->sum('difference');
            $stokMasuk  += $sumOpnamePos;
            $stokKeluar += abs($sumOpnameNeg);

            // --- FINAL STOCK END ---
            $stockEnd = $stockStart + ($stokMasuk - $stokKeluar);

            // Push the row—every value is guaranteed to be an int (0 if no movements)
            $rows[] = [
                $p->name,
                (int)$stockStart,
                (int)$stokMasuk,
                (int)$stokKeluar,
                (int)$stockEnd,
            ];
        }

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
