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
        $this->start = $start->startOfDay();
        $this->end   = $end->endOfDay();
    }

    /**
     * Build one big array:
     * [
     *   [ "Laporan Stok dd-mm-YYYY s/d dd-mm-YYYY" ],  // row 1
     *   [ ],                                            // row 2 (blank)
     *   [ "Product Name", "Stock Start", "Stok Masuk", "Stok Keluar", "Stock End" ], // row 3 headings
     *   [ ... per-product data ... ]                    // row 4+, data rows
     * ]
     */
    public function array(): array
    {
        $rows = [];

        // 1) Title row
        $titleText = "Laporan Stok {$this->start->format('d-m-Y')} s/d {$this->end->format('d-m-Y')}";
        $rows[] = [$titleText];

        // 2) Blank row
        $rows[] = [];

        // 3) Headings
        $rows[] = [
            'Product Name',
            'Stock Start',
            'Stok Masuk',
            'Stok Keluar',
            'Stock End',
        ];

        // 4) Data rows (one per product)
        $products = Product::all();
        foreach ($products as $product) {
            // -- Compute Stock Start (all movements < $start) --
            $stockStart = 0;

            // a) Sales before start finish_at → turun stok (keluar)
            $salesBefore = DetailTransaction::with('transaction')
                ->where('product_id', $product->id)
                ->whereHas('transaction', function ($q) {
                    $q->where('transaction_at', '<', $this->start);
                })
                ->get();
            foreach ($salesBefore as $saleItem) {
                $stockStart -= $saleItem->qty;
            }

            // b) Purchases before start finish_at → naik stok (masuk)
            $purchasesBefore = ProductPurchase::with('purchase')
                ->where('product_id', $product->id)
                ->whereHas('purchase', function ($q) {
                    $q->where('entryDate', '<', $this->start);
                })
                ->get();
            foreach ($purchasesBefore as $pp) {
                $stockStart += $pp->qty;
            }

            // c) Returns before start date
            $retursBefore = ReturItem::with('retur')
                ->where('product_id', $product->id)
                ->whereHas('retur', function ($q) {
                    $q->where('return_date', '<', $this->start);
                })
                ->get();
            foreach ($retursBefore as $r) {
                if ($r->retur->return_type === 'customer') {
                    if ($r->condition === 'good') {
                        $stockStart += $r->qty;      // Retur customer baik → masuk
                    }
                    // kalau rusak, tidak menambah stok
                } else {
                    // Retur supplier → keluar stok
                    $stockStart -= $r->qty;
                }
            }

            // d) Stock Opname before start date
            $opnamesBefore = DetailStokOpname::with('schedule')
                ->where('product_id', $product->id)
                ->whereHas('schedule', function ($q) {
                    $q->where('finish_at', '<', $this->start);
                })
                ->get();
            foreach ($opnamesBefore as $op) {
                $stockStart += $op->difference; // difference bisa positif (masuk) atau negatif (keluar)
            }

            // -- Compute Change (all movements between $start..$end) --
            $stokMasuk  = 0;
            $stokKeluar = 0;

            // a) Sales in range → turun stok (qty masuk ke Keluar)
            $salesInRange = DetailTransaction::with('transaction')
                ->where('product_id', $product->id)
                ->whereHas('transaction', function ($q) {
                    $q->whereBetween('transaction_at', [$this->start, $this->end]);
                })
                ->get();
            foreach ($salesInRange as $saleItem) {
                $stokKeluar += $saleItem->qty;
            }

            // b) Purchases in range → naik stok (masuk)
            $purchasesInRange = ProductPurchase::with('purchase')
                ->where('product_id', $product->id)
                ->whereHas('purchase', function ($q) {
                    $q->whereBetween('entryDate', [$this->start, $this->end]);
                })
                ->get();
            foreach ($purchasesInRange as $pp) {
                $stokMasuk += $pp->qty;
            }

            // c) Returns in range
            $retursInRange = ReturItem::with('retur')
                ->where('product_id', $product->id)
                ->whereHas('retur', function ($q) {
                    $q->whereBetween('return_date', [$this->start, $this->end]);
                })
                ->get();
            foreach ($retursInRange as $r) {
                if ($r->retur->return_type === 'customer') {
                    if ($r->condition === 'good') {
                        $stokMasuk += $r->qty;   // Retur customer baik → masuk
                    }
                    // rusak: tidak menambah stok
                } else {
                    // Retur supplier → keluar stok
                    $stokKeluar += $r->qty;
                }
            }

            // d) Stock Opname in range
            $opnamesInRange = DetailStokOpname::with('schedule')
                ->where('product_id', $product->id)
                ->whereHas('schedule', function ($q) {
                    $q->whereBetween('finish_at', [$this->start, $this->end]);
                })
                ->get();
            foreach ($opnamesInRange as $op) {
                if ($op->difference > 0) {
                    $stokMasuk += $op->difference;
                } else {
                    $stokKeluar += abs($op->difference);
                }
            }

            // -- Stock End = Stock Start + (Masuk - Keluar) --
            $stockEnd = $stockStart + ($stokMasuk - $stokKeluar);

            // Add this product’s row
            $rows[] = [
                $product->name,
                $stockStart,
                $stokMasuk,
                $stokKeluar,
                $stockEnd,
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Ringkasan Stok';
    }

    /**
     * Sesuaikan lebar kolom agar tidak collapse.
     * A → Product Name
     * B → Stock Start
     * C → Stok Masuk
     * D → Stok Keluar
     * E → Stock End
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,   // lebarkan kolom Product Name
            'B' => 15,   // Stock Start
            'C' => 15,   // Stok Masuk
            'D' => 15,   // Stok Keluar
            'E' => 15,   // Stock End
        ];
    }
}
