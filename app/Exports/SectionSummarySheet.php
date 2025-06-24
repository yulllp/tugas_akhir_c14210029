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

        // Title row
        $rows[] = ["Laporan Stok {$this->start->format('d-m-Y')} s/d {$this->end->format('d-m-Y')}"];

        $rows[] = [];

        $rows[] = ['Nama Produk', 'Stok Awal', 'Stok Masuk', 'Stok Keluar', 'Stok Akhir'];

        $products = Product::where('created_at', '<=', $this->end)->get();

        foreach ($products as $p) {
            $stockStart = 0;

            
            $sumSalesBefore = (int) DetailTransaction::where('product_id', $p->id)
                ->whereHas('transaction', fn($q) => $q->where('transaction_at', '<', $this->start))
                ->sum('qty');
            $stockStart -= $sumSalesBefore;

            
            $sumPurchasesBefore = (int) ProductPurchase::where('product_id', $p->id)
                ->whereHas('purchase', fn($q) => $q->where('entryDate', '<', $this->start))
                ->sum('qty');
            $stockStart += $sumPurchasesBefore;

            $sumReturCustGoodBefore = (int) ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q
                    ->where('return_date', '<', $this->start)
                    ->where('return_type', 'customer'))
                ->where('condition', 'good')
                ->sum('qty');
            $stockStart += $sumReturCustGoodBefore;

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

            $stokMasuk = 0;
            $stokKeluar = 0;

          
            $stokKeluar += (int) DetailTransaction::where('product_id', $p->id)
                ->whereHas('transaction', fn($q) => $q->whereBetween('transaction_at', [$this->start, $this->end]))
                ->sum('qty');

    
            $stokMasuk += (int) ProductPurchase::where('product_id', $p->id)
                ->whereHas('purchase', fn($q) => $q->whereBetween('entryDate', [$this->start, $this->end]))
                ->sum('qty');


            $stokMasuk += (int) ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q
                    ->whereBetween('return_date', [$this->start, $this->end])
                    ->where('return_type', 'customer'))
                ->where('condition', 'good')
                ->sum('qty');


            $stokKeluar += (int) ReturItem::where('product_id', $p->id)
                ->whereHas('retur', fn($q) => $q
                    ->whereBetween('return_date', [$this->start, $this->end])
                    ->where('return_type', 'supplier'))
                ->sum('qty');


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
                $p->name,
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