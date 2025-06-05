<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashFlowDetailExport implements FromArray, WithHeadings, WithEvents, WithTitle, WithStyles, ShouldAutoSize, ShouldQueue
{
    /**
     * @var \Carbon\Carbon|null
     */
    protected $start;

    /**
     * @var \Carbon\Carbon|null
     */
    protected $end;

    /**
     * @var array
     */
    protected $activities;

    /**
     * @var array
     */
    protected $rows = [];

    /**
     * Order of sections in the sheet
     */
    protected const SECTION_ORDER = [
        'transactions',         // transaksi
        'purchases',            // pembelian
    ];

    public function __construct($start = null, $end = null, array $activities = [])
    {
        $this->start = $start ? Carbon::parse($start, 'Asia/Jayapura')->startOfDay() : null;
        $this->end   = $end ? Carbon::parse($end, 'Asia/Jayapura')->endOfDay() : null;
        $this->activities = empty($activities) ? ['all'] : $activities;
    }


    /**
     * Build the full sheet rows.
     */
    protected function buildRows(): void
    {
        $periodLabel = $this->start && $this->end
            ? $this->start->format('d-m-Y') . ' s.d. ' . $this->end->format('d-m-Y')
            : 'Semua Periode';

        // Title & period
        $this->rows[] = ["Laporan Arus Kas ($periodLabel)"];
        $this->rows[] = []; // spacer

        // Build each section's data
        $transactionRows = $this->buildTransactions();
        $returCustomerRows = $this->buildCustomerReturns();
        $purchaseRows = $this->buildPurchases();
        $returSupplierRows = $this->buildSupplierReturns();

        // Append each section
        $this->rows[] = ['TRANSAKSI'];
        foreach ($transactionRows as $row) {
            $this->rows[] = $row;
        }
        $this->rows[] = []; // spacer

        $this->rows[] = ['RETUR CUSTOMER'];
        foreach ($returCustomerRows as $row) {
            $this->rows[] = $row;
        }
        $this->rows[] = []; // spacer

        $this->rows[] = ['PEMBELIAN'];
        foreach ($purchaseRows as $row) {
            $this->rows[] = $row;
        }
        $this->rows[] = []; // spacer

        $this->rows[] = ['RETUR SUPPLIER'];
        foreach ($returSupplierRows as $row) {
            $this->rows[] = $row;
        }
        $this->rows[] = []; // spacer

        // Summary
        $this->rows[] = ['RINGKASAN ARUS KAS'];
        $summary = $this->calculateSummary($this->activities);
        $this->rows[] = ['Total Kas Masuk', $summary['in'] ?? 0];
        $this->rows[] = ['Total Kas Keluar', $summary['out'] ?? 0];
        $this->rows[] = ['Total Laba / Rugi', $summary['profit'] ?? 0];
    }



    protected function activityEnabled($key)
    {
        return in_array('all', $this->activities) || in_array($key, $this->activities);
    }

    protected function buildTransactions(): array
    {
        if (!$this->activityEnabled('transactions')) return [];

        $rows = [['Detail Transaksi']];
        $processedTransactionIds = [];

        $transactions = DB::table('transactions as t')
            ->join('detail_transactions as dt', 't.id', '=', 'dt.transaction_id')
            ->leftJoin('products as p', 'p.id', '=', 'dt.product_id')
            ->leftJoin('product_prices as pp', 'pp.id', '=', 'dt.product_price_id')
            ->whereNull('t.deleted_at')
            ->when($this->start && $this->end, fn($q) => $q->whereBetween('t.transaction_at', [$this->start, $this->end]))
            ->select([
                't.id as transaction_id',
                't.transaction_at as date',
                't.total',
                DB::raw('t."prePaid" as prepaid'),
                'p.name as product',
                'dt.qty',
                DB::raw('pp."sellPrice" as price'),
                'dt.discount',
                DB::raw('(dt.qty * pp."sellPrice") - (dt.qty * dt.discount) as subtotal'),
            ])
            ->orderBy('t.transaction_at')
            ->get()
            ->groupBy('transaction_id');

        foreach ($transactions as $transactionId => $items) {
            $first = $items->first();
            $processedTransactionIds[] = $transactionId;

            $rows[] = ['Tanggal: ' . Carbon::parse($first->date)->format('d-m-Y') . ' | Kode: ' . $transactionId];
            $rows[] = ['Produk', 'Qty', 'Harga', 'Diskon (Qty x Diskon)', 'Subtotal'];

            foreach ($items as $item) {
                $rows[] = [
                    $item->product,
                    $item->qty,
                    $item->price,
                    $item->qty * $item->discount,
                    $item->subtotal,
                ];
            }

            $futureCredits = DB::table('credit_payments')
                ->where('transaction_id', $transactionId)
                ->where('payDate', '>', $this->end)
                ->sum('payment_total');

            $rows[] = ['', '', '', 'Prepaid', 'value' => $first->prepaid - $futureCredits];
            $rows[] = ['', '', '', 'Total', $first->total];
            $rows[] = [''];
        }

        return $rows;
    }

    protected function buildCustomerReturns(): array
    {
        if (!$this->activityEnabled('transactions')) return [];

        $rows = [['Retur Penjualan']];

        // Combined all returns regardless of linkage
        $returns = DB::table('retur_items as ri')
            ->join('returs as r', 'r.id', '=', 'ri.retur_id')
            ->leftJoin('products as p', 'p.id', '=', 'ri.product_id')
            ->leftJoin('product_prices as pp', 'pp.id', '=', 'ri.product_price_id')
            ->where('r.return_type', 'customer')
            ->whereNull('r.deleted_at')
            ->whereBetween('r.return_date', [$this->start, $this->end])
            ->select([
                'r.transaction_id',
                'r.return_date',
                'p.name as product',
                'ri.qty',
                DB::raw('pp."sellPrice" as price'),
                'ri.disc',
                'ri.condition',
                DB::raw('ri.subtotal as subtotal')
            ])
            ->get()
            ->groupBy('transaction_id');

        foreach ($returns as $transactionId => $items) {
            $rows[] = ['Retur dari Transaksi | Kode: ' . ($transactionId ?? '-')];

            $grouped = $items->groupBy(fn($item) => Carbon::parse($item->return_date)->format('d-m-Y'));

            foreach ($grouped as $date => $returItems) {
                $rows[] = ['Tanggal Retur: ' . $date];
                $rows[] = ['Produk', 'Qty', 'Harga', 'Diskon (Qty x Diskon)', 'Subtotal', 'Jenis Retur'];

                $returTotal = 0;

                foreach ($returItems as $r) {
                    $rows[] = [
                        $r->product,
                        $r->qty,
                        $r->price,
                        $r->qty * $r->disc,
                        $r->subtotal,
                        $r->condition,
                    ];
                    $returTotal += $r->subtotal;
                }

                $rows[] = ['', '', '', '', 'value' => 'Total Retur: ' . number_format($returTotal, 0, ',', '.')];
            }

            $rows[] = [''];
        }

        return $rows;
    }

    protected function buildPurchases(): array
    {
        if (!$this->activityEnabled('purchases')) return [];

        $rows = [['Detail Pembelian']];
        $processedPurchaseIds = [];

        $purchases = DB::table('purchases as pu')
            ->join('product_purchases as pp', 'pu.id', '=', 'pp.purchase_id')
            ->leftJoin('products as p', 'p.id', '=', 'pp.product_id')
            ->whereNull('pu.deleted_at')
            ->when($this->start && $this->end, fn($q) => $q->whereBetween('pu.buyDate', [$this->start, $this->end]))
            ->select([
                'pu.id as purchase_id',
                'pu.buyDate as date',
                'pu.total',
                DB::raw('pu."prePaid" as prepaid'),
                'p.name as product',
                'pp.qty',
                'pp.buyPrice',
                DB::raw('pp.subtotal as subtotal'),
            ])
            ->orderBy('pu.buyDate')
            ->get()
            ->groupBy('purchase_id');

        foreach ($purchases as $purchaseId => $items) {
            $first = $items->first();
            $processedPurchaseIds[] = $purchaseId;

            $rows[] = ['Tanggal: ' . Carbon::parse($first->date)->format('d-m-Y') . ' | Kode: ' . $purchaseId];
            $rows[] = ['Produk', 'Qty', 'Harga Beli', 'Subtotal'];

            foreach ($items as $item) {
                $rows[] = [
                    $item->product,
                    $item->qty,
                    $item->buyPrice,
                    $item->subtotal,
                ];
            }

            $futureCredits = DB::table('credit_purchases')
                ->where('purchase_id', $purchaseId)
                ->where('payDate', '>', $this->end)
                ->sum('payment_total');

            $rows[] = ['', '', 'Prepaid', $first->prepaid - $futureCredits];
            $rows[] = ['', '', 'Total', $first->total];
            $rows[] = [''];
        }

        return $rows;
    }

    protected function buildSupplierReturns(): array
    {
        if (!$this->activityEnabled('purchases')) return [];

        $rows = [['Retur Pembelian']];

        $supplierReturns = DB::table('retur_items as ri')
            ->join('returs as r', 'r.id', '=', 'ri.retur_id')
            ->leftJoin('products as p', 'p.id', '=', 'ri.product_id')
            ->where('r.return_type', 'supplier')
            ->whereNull('r.deleted_at')
            ->whereBetween('r.return_date', [$this->start, $this->end])
            ->select([
                'r.purchase_id',
                'r.return_date',
                'p.name as product',
                'ri.qty',
                'ri.buy_price as price',
                DB::raw('ri.subtotal as subtotal')
            ])
            ->get()
            ->groupBy('purchase_id');

        foreach ($supplierReturns as $purchaseId => $items) {
            $rows[] = ['Kode Pembelian: ' . $purchaseId];

            $grouped = $items->groupBy(fn($item) => Carbon::parse($item->return_date)->format('d-m-Y'));

            foreach ($grouped as $date => $returItems) {
                $rows[] = ['Tanggal Retur: ' . $date];
                $rows[] = ['Produk', 'Qty', 'Harga', 'Subtotal'];

                $returTotal = 0;

                foreach ($returItems as $r) {
                    $rows[] = [
                        $r->product,
                        $r->qty,
                        $r->price,
                        $r->subtotal,
                    ];
                    $returTotal += $r->subtotal;
                }

                $rows[] = ['', '', '', 'value' => 'Total Retur: ' . number_format($returTotal, 0, ',', '.')];
            }

            $rows[] = [''];
        }

        return $rows;
    }


    /**
     * Calculate cash summary
     */
    protected function calculateSummary(array $activities): array
    {
        $cashIn = 0;
        $cashOut = 0;

        if (in_array('transactions', $activities)) {
            // Prepaid part of transactions minus future credit payments
            $transactions = DB::table('transactions as t')
                ->leftJoin('credit_payments as cp', 'cp.transaction_id', '=', 't.id')
                ->whereNull('t.deleted_at');
            if ($this->start && $this->end) {
                $transactions->whereBetween('t.transaction_at', [$this->start, $this->end]);
            }
            $cashIn += $transactions->sum(DB::raw('t."prePaid" - COALESCE(cp.payment_total, 0)'));

            // Credit payments made within date range
            $creditPayments = DB::table('credit_payments')
                ->whereNull('deleted_at')
                ->when($this->start && $this->end, fn($q) => $q->whereBetween(DB::raw('"payDate"'), [$this->start, $this->end]));
            $cashIn += $creditPayments->sum('payment_total');

            // Customer return (cash paid back to customers)
            $customerReturns = DB::table('retur_items as ri')
                ->join('returs as r', 'r.id', '=', 'ri.retur_id')
                ->where('r.return_type', 'customer')
                ->whereNull('r.deleted_at')
                ->when($this->start && $this->end, fn($q) => $q->whereBetween('r.return_date', [$this->start, $this->end]));
            $cashOut += $customerReturns->sum(DB::raw('ri.subtotal'));
        }

        if (in_array('purchases', $activities)) {
            // Prepaid part of purchases minus future credit payments
            $purchases = DB::table('purchases as pu')
                ->leftJoin('credit_purchases as cp', 'cp.purchase_id', '=', 'pu.id')
                ->whereNull('pu.deleted_at');
            if ($this->start && $this->end) {
                $purchases->whereBetween('pu.buyDate', [$this->start, $this->end]);
            }
            $cashOut += $purchases->sum(DB::raw('pu."prePaid" - COALESCE(cp.payment_total, 0)'));

            // Credit purchases paid within date range
            $creditPurchases = DB::table('credit_purchases')
                ->whereNull('deleted_at')
                ->when($this->start && $this->end, fn($q) => $q->whereBetween(DB::raw('"payDate"'), [$this->start, $this->end]));
            $cashOut += $creditPurchases->sum('payment_total');

            // Supplier return (money refunded from supplier)
            $supplierReturns = DB::table('retur_items as ri')
                ->join('returs as r', 'r.id', '=', 'ri.retur_id')
                ->where('r.return_type', 'supplier')
                ->whereNull('r.deleted_at')
                ->when($this->start && $this->end, fn($q) => $q->whereBetween('r.return_date', [$this->start, $this->end]));
            $cashIn += $supplierReturns->sum(DB::raw('ri.subtotal'));
        }

        return [
            'in'     => $cashIn,
            'out'    => $cashOut,
            'profit' => $cashIn - $cashOut,
        ];
    }

    /* ------------------------------------ */
    /*  Maatwebsite Concerns Implementations */
    /* ------------------------------------ */

    public function array(): array
    {
        if (empty($this->rows)) {
            $this->buildRows();
        }
        return $this->rows;
    }

    public function headings(): array
    {
        // The headings concern will only set the first heading row.
        return [];
    }

    public function registerEvents(): array
    {
        return [
            // AfterSheet::class => function (AfterSheet $event) {
            //     $sheet = $event->sheet;

            //     // Title row merge & style
            //     $sheet->mergeCells('A1:J1');
            //     $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            //     $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

            //     // Auto-filter for every header row (bold font)
            //     foreach ($this->rows as $index => $row) {
            //         // Skip if not an array or doesn't have index 0
            //         if (!is_array($row) || !isset($row[0])) {
            //             continue;
            //         }
            //         // Check for heading rows
            //         if (
            //             strpos($row[0], 'Detail') === 0 ||
            //             strpos($row[0], 'Retur') === 0 ||
            //             strpos($row[0], 'Pembayaran') === 0 ||
            //             strpos($row[0], 'Pelunasan') === 0
            //         ) {
            //             $rowNumber = $index + 1; // Excel rows are 1-based
            //             $sheet->getStyle("A{$rowNumber}:J{$rowNumber}")->getFont()->setBold(true);
            //         }
            //     }
            // },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // // Bold the summary labels
            // 'A' . (count($this->rows) - 2) => ['font' => ['bold' => true]],
            // 'A' . (count($this->rows) - 1) => ['font' => ['bold' => true]],
            // 'A' . (count($this->rows))     => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Arus Kas';
    }
}
