<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;

class CashFlowDetailExport implements FromArray, WithHeadings, WithEvents, WithTitle, WithStyles, ShouldAutoSize, ShouldQueue
{
    protected $start;
    protected $end;
    protected $activities;
    protected $rows = [];

    /**
     * @param string|null $start      
     * @param string|null $end        
     * @param array       $activities 
     */
    public function __construct($start = null, $end = null, array $activities = [])
    {
        $this->start = $start
            ? Carbon::parse($start, 'Asia/Jayapura')->startOfDay()
            : null;
        $this->end = $end
            ? Carbon::parse($end, 'Asia/Jayapura')->endOfDay()
            : null;
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

        $this->rows[] = ["Laporan Arus Kas ($periodLabel)"];
        $this->rows[] = ['']; // one blank

        $this->rows[] = ['TRANSAKSI (CASH-IN)'];
        if ($this->activityEnabled('transactions')) {
            $this->appendAllTransactions();
        } else {
            $this->rows[] = ['-- Tidak ada data transaksi --'];
        }
        $this->rows[] = [''];
        $this->rows[] = [''];

        $this->rows[] = ['PEMBAYARAN KREDIT TRANSAKSI (CASH-IN)'];
        if ($this->activityEnabled('transactions')) {
            $this->appendAllCreditPaymentsOnTransactions();
        } else {
            $this->rows[] = ['-- Tidak ada data pembayaran kredit transaksi --'];
        }
        // two blanks before next section
        $this->rows[] = [''];
        $this->rows[] = [''];

        $this->rows[] = ['RETUR PELANGGAN (CASH-OUT)'];
        if ($this->activityEnabled('transactions')) {
            $this->appendAllCustomerReturns();
        } else {
            $this->rows[] = ['-- Tidak ada data retur pelanggan --'];
        }
        // two blanks before next section
        $this->rows[] = [''];
        $this->rows[] = [''];

        $this->rows[] = ['PEMBELIAN (CASH-OUT)'];
        if ($this->activityEnabled('purchases')) {
            $this->appendAllPurchases();
        } else {
            $this->rows[] = ['-- Tidak ada data pembelian --'];
        }
        // two blanks before next section
        $this->rows[] = [''];
        $this->rows[] = [''];

        $this->rows[] = ['PEMBAYARAN KREDIT PEMBELIAN (CASH-OUT)'];
        if ($this->activityEnabled('purchases')) {
            $this->appendAllCreditPaymentsOnPurchases();
        } else {
            $this->rows[] = ['-- Tidak ada data pembayaran kredit pembelian --'];
        }
        // two blanks before next section
        $this->rows[] = [''];
        $this->rows[] = [''];

        $this->rows[] = ['RETUR SUPPLIER (CASH-IN)'];
        if ($this->activityEnabled('purchases')) {
            $this->appendAllSupplierReturns();
        } else {
            $this->rows[] = ['-- Tidak ada data retur supplier --'];
        }
        // two blanks before summary
        $this->rows[] = [''];
        $this->rows[] = [''];

        //
        // SUMMARY
        //
        $this->rows[] = ['RINGKASAN ARUS KAS'];
        $summary = $this->calculateSummary();
        $this->rows[] = ['Total Kas Masuk',   $summary['in']     ?? 0];
        $this->rows[] = ['Total Kas Keluar',  $summary['out']    ?? 0];
        $this->rows[] = ['Total Laba / Rugi', $summary['profit'] ?? 0];
    }

    protected function activityEnabled(string $key): bool
    {
        return in_array('all', $this->activities) || in_array($key, $this->activities);
    }

    protected function appendAllTransactions(): void
    {
        // Fetch each transaction header and items in one go
        $q = DB::table('transactions as t')
            ->join('detail_transactions as dt', 't.id', '=', 'dt.transaction_id')
            ->leftJoin('products as p', 'p.id', '=', 'dt.product_id')
            ->leftJoin('product_prices as pp', 'pp.id', '=', 'dt.product_price_id')
            ->whereNull('t.deleted_at');

        if ($this->start && $this->end) {
            $q->whereBetween('t.transaction_at', [$this->start, $this->end]);
        }

        $allRows = $q->select([
            't.id as transaction_id',
            't.transaction_at as date',
            't.prePaid',
            'p.name as product',
            'dt.qty',
            DB::raw('pp."sellPrice" as price'),
            'dt.discount',
            DB::raw('(dt.qty * pp."sellPrice") - (dt.qty * dt.discount) as item_subtotal'),
        ])
            ->orderBy('t.transaction_at')
            ->get()
            ->groupBy('transaction_id');

        if ($allRows->isEmpty()) {
            $this->rows[] = ['-- Tidak ada data transaksi di periode ini --'];
            return;
        }

        foreach ($allRows as $txId => $items) {
            $first = $items->first();
            $dateFormatted = Carbon::parse($first->date)->format('d-m-Y H:i');

            // Header for this transaction
            $this->rows[] = [
                'Tanggal: ' . $dateFormatted
                    . ' | Kode Transaksi: ' . $txId
                    . ' | Prepaid: ' . $first->prePaid
            ];

            // Column titles for items
            $this->rows[] = ['Produk', 'Qty', 'Harga Satuan', 'Diskon per Item', 'Subtotal Item'];

            // List every item
            foreach ($items as $item) {
                $this->rows[] = [
                    $item->product,
                    $item->qty,
                    // raw integer price
                    $item->price,
                    // raw integer discount
                    $item->discount,
                    // raw integer item_subtotal
                    $item->item_subtotal,
                ];
            }

            // one blank before next transaction
            $this->rows[] = [''];
        }
    }

    protected function appendAllCreditPaymentsOnTransactions(): void
    {
        $q = DB::table('credit_payments as cp')
            ->join('transactions as t', 't.id', '=', 'cp.transaction_id')
            ->whereNull('cp.deleted_at');

        if ($this->start && $this->end) {
            $q->whereBetween('cp.payDate', [$this->start, $this->end]);
        }

        $credits = $q->select([
            'cp.id as credit_id',
            'cp.transaction_id',
            'cp.payDate',
            'cp.payment_total',
        ])
            ->orderBy('cp.payDate')
            ->get();

        if ($credits->isEmpty()) {
            $this->rows[] = ['-- Tidak ada pembayaran kredit transaksi di periode ini --'];
            return;
        }

        // Column titles
        $this->rows[] = ['ID Pembayaran', 'Kode Transaksi', 'Tanggal Pembayaran', 'Jumlah'];

        foreach ($credits as $cp) {
            $this->rows[] = [
                $cp->credit_id,
                $cp->transaction_id,
                Carbon::parse($cp->payDate)->format('d-m-Y H:i'),
                // raw integer payment_total
                $cp->payment_total,
            ];
        }

        // one blank after entire section
        $this->rows[] = [''];
    }

    protected function appendAllCustomerReturns(): void
    {
        $q = DB::table('returs as r')
            ->where('r.return_type', 'customer')
            ->whereNull('r.deleted_at');

        if ($this->start && $this->end) {
            $q->whereBetween('r.return_date', [$this->start, $this->end]);
        }

        $returns = $q->select([
            'r.id as retur_id',
            'r.transaction_id',
            'r.return_date',
            'r.refund_amount',
        ])
            ->orderBy('r.return_date')
            ->get();

        if ($returns->isEmpty()) {
            $this->rows[] = ['-- Tidak ada data retur pelanggan di periode ini --'];
            return;
        }

        foreach ($returns as $r) {
            $this->rows[] = [
                'ID Retur: ' . $r->retur_id
                    . ' | Kode Transaksi: ' . ($r->transaction_id ?? '-')
                    . ' | Tanggal: ' . Carbon::parse($r->return_date)->format('d-m-Y H:i')
                    . ' | Refund Amount: ' . $r->refund_amount
            ];

            // (optional) show item‐level details for that return
            $items = DB::table('retur_items as ri')
                ->join('products as p', 'p.id', '=', 'ri.product_id')
                ->leftJoin('product_prices as pp', 'pp.id', '=', 'ri.product_price_id')
                ->where('ri.retur_id', $r->retur_id)
                ->select([
                    'p.name as product',
                    'ri.qty',
                    DB::raw('COALESCE(pp."sellPrice", ri.buy_price) as price'),
                    'ri.disc as discount',
                    'ri.condition',
                    DB::raw('ri.subtotal as item_subtotal'),
                ])
                ->get();

            $this->rows[] = ['Produk', 'Qty', 'Harga Satuan', 'Diskon per item', 'Kondisi', 'Subtotal Item'];
            foreach ($items as $ri) {
                $this->rows[] = [
                    $ri->product,
                    $ri->qty,
                    $ri->price,
                    $ri->discount,
                    ucfirst($ri->condition),
                    $ri->item_subtotal,
                ];
            }

            // one blank after each customer return
            $this->rows[] = [''];
        }
    }

    protected function appendAllPurchases(): void
    {
        $q = DB::table('purchases as pu')
            ->join('product_purchases as pp', 'pu.id', '=', 'pp.purchase_id')
            ->leftJoin('products as p', 'p.id', '=', 'pp.product_id')
            ->whereNull('pu.deleted_at');

        if ($this->start && $this->end) {
            $q->whereBetween('pu.buyDate', [$this->start, $this->end]);
        }

        $allRows = $q->select([
            'pu.id as purchase_id',
            'pu.buyDate as date',
            'pu.prePaid',
            'p.name as product',
            'pp.qty',
            'pp.buyPrice as price',
            'pp.subtotal as item_subtotal',
        ])
            ->orderBy('pu.buyDate')
            ->get()
            ->groupBy('purchase_id');

        if ($allRows->isEmpty()) {
            $this->rows[] = ['-- Tidak ada data pembelian di periode ini --'];
            return;
        }

        foreach ($allRows as $purchaseId => $items) {
            $first = $items->first();
            $dateFormatted = Carbon::parse($first->date)->format('d-m-Y H:i');

            $this->rows[] = [
                'Tanggal: ' . $dateFormatted
                    . ' | Kode Pembelian: ' . $purchaseId
                    . ' | Prepaid: ' . $first->prePaid
            ];

            // Column titles
            $this->rows[] = ['Produk', 'Qty', 'Harga Beli', 'Subtotal Item'];

            foreach ($items as $item) {
                $this->rows[] = [
                    $item->product,
                    $item->qty,
                    $item->price,
                    $item->item_subtotal,
                ];
            }

            // one blank after each purchase
            $this->rows[] = [''];
        }
    }

    protected function appendAllCreditPaymentsOnPurchases(): void
    {
        $q = DB::table('credit_purchases as cp')
            ->join('purchases as pu', 'pu.id', '=', 'cp.purchase_id')
            ->whereNull('cp.deleted_at');

        if ($this->start && $this->end) {
            $q->whereBetween('cp.payDate', [$this->start, $this->end]);
        }

        $credits = $q->select([
            'cp.id as credit_id',
            'cp.purchase_id',
            'cp.payDate',
            'cp.payment_total',
        ])
            ->orderBy('cp.payDate')
            ->get();

        if ($credits->isEmpty()) {
            $this->rows[] = ['-- Tidak ada data pembayaran kredit pembelian di periode ini --'];
            return;
        }

        $this->rows[] = ['ID Pembayaran', 'Kode Pembelian', 'Tanggal Pembayaran', 'Jumlah'];
        foreach ($credits as $cp) {
            $this->rows[] = [
                $cp->credit_id,
                $cp->purchase_id,
                Carbon::parse($cp->payDate)->format('d-m-Y H:i'),
                $cp->payment_total,
            ];
        }

        // one blank after entire section
        $this->rows[] = [''];
    }

    protected function appendAllSupplierReturns(): void
    {
        $q = DB::table('returs as r')
            ->where('r.return_type', 'supplier')
            ->whereNull('r.deleted_at');

        if ($this->start && $this->end) {
            $q->whereBetween('r.return_date', [$this->start, $this->end]);
        }

        $returns = $q->select([
            'r.id as retur_id',
            'r.purchase_id',
            'r.return_date',
            'r.refund_amount',
        ])
            ->orderBy('r.return_date')
            ->get();

        if ($returns->isEmpty()) {
            $this->rows[] = ['-- Tidak ada data retur supplier di periode ini --'];
            return;
        }

        foreach ($returns as $r) {
            $this->rows[] = [
                'ID Retur: ' . $r->retur_id
                    . ' | Kode Pembelian: ' . ($r->purchase_id ?? '-')
                    . ' | Tanggal: ' . Carbon::parse($r->return_date)->format('d-m-Y H:i')
                    . ' | Refund Amount: ' . $r->refund_amount
            ];

            // Optional: show item‐level details
            $items = DB::table('retur_items as ri')
                ->join('products as p', 'p.id', '=', 'ri.product_id')
                ->where('ri.retur_id', $r->retur_id)
                ->select([
                    'p.name as product',
                    'ri.qty',
                    'ri.buy_price as price',
                    'ri.subtotal as item_subtotal',
                ])
                ->get();

            $this->rows[] = ['Produk', 'Qty', 'Harga Beli', 'Subtotal Item'];
            foreach ($items as $ri) {
                $this->rows[] = [
                    $ri->product,
                    $ri->qty,
                    $ri->price,
                    $ri->item_subtotal,
                ];
            }

            // one blank after each supplier return
            $this->rows[] = [''];
        }
    }

    /**
     * Summarize cash‐in / cash‐out exactly as in the controller logic.
     */
    protected function calculateSummary(): array
    {
        $cashIn = 0;
        $cashOut = 0;

        // TRANSAKSI (prePaid cash‐in)
        if ($this->activityEnabled('transactions')) {
            $sumPrepaid = DB::table('transactions as t')
                ->whereNull('t.deleted_at')
                ->when(
                    $this->start && $this->end,
                    fn($q) => $q->whereBetween('t.transaction_at', [$this->start, $this->end])
                )
                ->select(DB::raw('SUM(t."prePaid") as total_prepaid'))
                ->first()
                ->total_prepaid ?? 0;
            $cashIn += $sumPrepaid;
        }

        // Pembayaran kredit transaksi (cash‐in)
        if ($this->activityEnabled('transactions')) {
            $sumCreditTx = DB::table('credit_payments as cp')
                ->whereNull('cp.deleted_at')
                ->when(
                    $this->start && $this->end,
                    fn($q) => $q->whereBetween('cp.payDate', [$this->start, $this->end])
                )
                ->select(DB::raw('SUM(cp.payment_total) as total_credit_tx'))
                ->first()
                ->total_credit_tx ?? 0;
            $cashIn += $sumCreditTx;
        }

        // Retur pelanggan (cash‐out)
        if ($this->activityEnabled('transactions')) {
            $sumCustRet = DB::table('returs as r')
                ->where('r.return_type', 'customer')
                ->whereNull('r.deleted_at')
                ->when(
                    $this->start && $this->end,
                    fn($q) => $q->whereBetween('r.return_date', [$this->start, $this->end])
                )
                ->select(DB::raw('SUM(r.refund_amount) as total_refund_customer'))
                ->first()
                ->total_refund_customer ?? 0;
            $cashOut += $sumCustRet;
        }

        // PEMBELIAN (prePaid cash‐out)
        if ($this->activityEnabled('purchases')) {
            $sumPrepaidPu = DB::table('purchases as pu')
                ->whereNull('pu.deleted_at')
                ->when(
                    $this->start && $this->end,
                    fn($q) => $q->whereBetween('pu.buyDate', [$this->start, $this->end])
                )
                ->select(DB::raw('SUM(pu."prePaid") as total_prepaid_pu'))
                ->first()
                ->total_prepaid_pu ?? 0;
            $cashOut += $sumPrepaidPu;
        }

        // Pembayaran kredit pembelian (cash‐out)
        if ($this->activityEnabled('purchases')) {
            $sumCreditPu = DB::table('credit_purchases as cp')
                ->whereNull('cp.deleted_at')
                ->when(
                    $this->start && $this->end,
                    fn($q) => $q->whereBetween('cp.payDate', [$this->start, $this->end])
                )
                ->select(DB::raw('SUM(cp.payment_total) as total_credit_purchase'))
                ->first()
                ->total_credit_purchase ?? 0;
            $cashOut += $sumCreditPu;
        }

        // Retur supplier (cash‐in)
        if ($this->activityEnabled('purchases')) {
            $sumSuppRet = DB::table('returs as r')
                ->where('r.return_type', 'supplier')
                ->whereNull('r.deleted_at')
                ->when(
                    $this->start && $this->end,
                    fn($q) => $q->whereBetween('r.return_date', [$this->start, $this->end])
                )
                ->select(DB::raw('SUM(r.refund_amount) as total_refund_supplier'))
                ->first()
                ->total_refund_supplier ?? 0;
            $cashIn += $sumSuppRet;
        }

        return [
            'in'     => $cashIn,
            'out'    => $cashOut,
            'profit' => $cashIn - $cashOut,
        ];
    }

    /* ------------------------------------------------------------
     * Maatwebsite\Excel\Concerns implementations
     * ------------------------------------------------------------ */

    public function array(): array
    {
        if (empty($this->rows)) {
            $this->buildRows();
        }
        return $this->rows;
    }

    public function headings(): array
    {
        // We build all headings inside buildRows(), so just return empty here.
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                foreach (['C', 'D', 'E', 'F', 'G'] as $col) {
                    $sheet
                        ->getStyle("{$col}1:{$col}{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // e.g. 'A1' => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }

    public function title(): string
    {
        return 'Arus Kas Detail';
    }
}
