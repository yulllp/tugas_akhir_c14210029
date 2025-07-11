<?php

namespace App\Exports;

use App\Models\DetailTransaction;
use App\Models\ProductPurchase;
use App\Models\ReturItem;
use App\Models\DetailStokOpname;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SectionDetailSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $start;
    protected $end;

    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    public function collection()
    {
        $rows = new Collection();

        // Sales within range
        $sales = DetailTransaction::with(['transaction', 'product'])
            ->whereHas('transaction', function ($q) {
                $q->whereBetween('transaction_at', [$this->start, $this->end]);
            })
            ->get();
        foreach ($sales as $item) {
            $rows->push([
                Carbon::parse($item->transaction->transaction_at)->format('d-m-Y H:i'),
                $item->product->name,
                'Penjualan',
                -$item->qty,
                'Penjualan #' . $item->transaction->id,
            ]);
        }

        // Purchases within range
        $purchases = ProductPurchase::with(['purchase', 'product'])
            ->whereHas('purchase', function ($q) {
                $q->whereBetween('entryDate', [$this->start, $this->end]);
            })
            ->get();
        foreach ($purchases as $item) {
            $rows->push([
                Carbon::parse($item->purchase->entryDate)->format('d-m-Y H:i'),
                $item->product->name,
                'Pembelian',
                $item->qty,
                'Pembelian #' . $item->purchase->id,
            ]);
        }

        // Returns within range
        $returs = ReturItem::with(['retur', 'product'])
            ->whereHas('retur', function ($q) {
                $q->whereBetween('return_date', [$this->start, $this->end]);
            })
            ->get();
        foreach ($returs as $item) {
            $date = Carbon::parse($item->retur->return_date)->format('d-m-Y H:i');
            $name = $item->product->name;

            if ($item->retur->return_type === 'customer') {
                if ($item->condition === 'good') {
                    $rows->push([
                        $date,
                        $name,
                        'Retur Customer (Baik)',
                        $item->qty,
                        'Retur #' . $item->retur->id . ' (Baik)',
                    ]);
                } else {
                    $rows->push([
                        $date,
                        $name,
                        'Retur Customer (Rusak)',
                        0,
                        'Retur #' . $item->retur->id . ' (Rusak)',
                    ]);
                }
            } else {
                // Supplier return: negative qty
                $rows->push([
                    $date,
                    $name,
                    'Retur Supplier',
                    -$item->qty,
                    'Retur Supplier #' . $item->retur->id,
                ]);
            }
        }

        // Stock Opname within range
        $opnames = DetailStokOpname::with(['schedule', 'product'])
            ->whereHas('schedule', function ($q) {
                $q->whereBetween('finish_at', [$this->start, $this->end]);
            })
            ->get();
        foreach ($opnames as $item) {
            $date = Carbon::parse($item->schedule->finish_at)->format('d-m-Y H:i');
            $rows->push([
                $date,
                $item->product->name,
                'Stok Opname',
                $item->difference,
                'Opname #' . $item->schedule->id,
            ]);
        }

        // Sort all rows by Date ascending
        $sorted = $rows->sortBy(function ($row) {
            // $row[0] is "Date" in d-m-Y; convert back to Carbon to sort properly
            return Carbon::createFromFormat('d-m-Y H:i', $row[0]);
        })->values();

        return $sorted;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Product Name',
            'Type',
            'Qty',
            'Description',
        ];
    }

    public function title(): string
    {
        return 'Detail Aktivitas';
    }
}
