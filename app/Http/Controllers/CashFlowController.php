<?php

namespace App\Http\Controllers;

use App\Exports\CashflowDetailExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $isSingleGroup = empty($request->start) && empty($request->end);

        if (! $isSingleGroup) {
            $start = Carbon::parse($request->start, 'Asia/Jayapura')->startOfDay();
            $end   = Carbon::parse($request->end,   'Asia/Jayapura')->endOfDay();

            if ($start->diffInYears($end) > 3) {
                return response()->json([
                    'error' => 'Rentang waktu maksimal adalah 3 tahun.'
                ], 422);
            }
        } else {
            $start = $end = null;
        }

        $activity = $request->activity ?? 'all';
        $cashType = $request->cash_type ?? 'all';

        $diffDays = ! $isSingleGroup ? $start->diffInDays($end) : 0;
        $groupBy  = $isSingleGroup ? 'total' : ($diffDays > 45 ? 'month' : 'date');
        $format   = $groupBy === 'month' ? 'MM-YYYY' : 'DD-MM-YYYY';

        $cashIns   = collect();
        $cashOuts  = collect();
        $activities = collect();

        //
        // 1) TRANSAKSI (CASH-IN; tidak lagi dikurangi kredit)
        //
        if ($cashType !== 'out' && ($activity === 'transaction' || $activity === 'all')) {
            $transactions = DB::table('transactions')
                ->when(! $isSingleGroup, fn($q) => $q->whereBetween('transaction_at', [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'transaction_at', 'prePaid')
                ->get();

            foreach ($transactions as $tx) {
                $activities->push([
                    // full datetime di format d-m-Y H:i
                    'date'      => Carbon::parse($tx->transaction_at)->format('d-m-Y H:i'),
                    'type'      => 'Transaksi #' . $tx->id,
                    'raw_type'  => 'transaction',
                    'id'        => $tx->id,
                    'amount'    => $tx->prePaid,
                    'direction' => 'in',
                ]);
            }

            $grouped = $transactions
                ->groupBy(
                    fn($tx) => $isSingleGroup
                        ? 'Total'
                        : Carbon::parse($tx->transaction_at)
                        ->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y')
                )
                ->map(fn($group) => $group->sum('prePaid'));

            $cashIns = $cashIns->mergeRecursive(
                $grouped->mapWithKeys(fn($v, $k) => [$k => ['transactions' => $v]])
            );
        }

        //
        // 2) PEMBAYARAN KREDIT TRANSAKSI (CASH-IN)
        //
        if ($cashType !== 'out' && ($activity === 'transaction' || $activity === 'all')) {
            $credits = DB::table('credit_payments')
                ->when(! $isSingleGroup, fn($q) => $q->whereBetween('payDate', [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'transaction_id', 'payDate', 'payment_total')
                ->get();

            foreach ($credits as $cr) {
                $activities->push([
                    'date'      => Carbon::parse($cr->payDate)->format('d-m-Y H:i'),
                    'type'      => 'Pembayaran Kredit Transaksi #' . $cr->transaction_id,
                    'raw_type'  => 'credit_payment',
                    'id'        => $cr->id,
                    'amount'    => $cr->payment_total,
                    'direction' => 'in',
                ]);
            }

            $grouped = $credits
                ->groupBy(
                    fn($c) => $isSingleGroup
                        ? 'Total'
                        : Carbon::parse($c->payDate)
                        ->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y')
                )
                ->map(fn($group) => $group->sum('payment_total'));

            $cashIns = $cashIns->mergeRecursive(
                $grouped->mapWithKeys(fn($v, $k) => [$k => ['credit_payments' => $v]])
            );
        }

        //
        // 3) PEMBELIAN (CASH-OUT; tidak lagi dikurangi kredit)
        //
        if ($cashType !== 'in' && ($activity === 'purchasing' || $activity === 'all')) {
            $purchases = DB::table('purchases')
                ->when(! $isSingleGroup, fn($q) => $q->whereBetween('buyDate', [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'buyDate', 'prePaid')
                ->get();

            foreach ($purchases as $p) {
                $activities->push([
                    'date'      => Carbon::parse($p->buyDate)->format('d-m-Y H:i'),
                    'type'      => 'Pembelian #' . $p->id,
                    'raw_type'  => 'purchase',
                    'id'        => $p->id,
                    'amount'    => -$p->prePaid, // jadikan negatif untuk cash-out
                    'direction' => 'out',
                ]);
            }

            $grouped = $purchases
                ->groupBy(
                    fn($p) => $isSingleGroup
                        ? 'Total'
                        : Carbon::parse($p->buyDate)
                        ->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y')
                )
                ->map(fn($group) => -$group->sum('prePaid')); // negatif untuk cash-out

            $cashOuts = $cashOuts->mergeRecursive(
                $grouped->mapWithKeys(fn($v, $k) => [$k => ['purchases' => $v]])
            );
        }

        //
        // 4) PELUNASAN KREDIT PEMBELIAN (CASH-OUT)
        //
        if ($cashType !== 'in' && ($activity === 'purchasing' || $activity === 'all')) {
            $credits = DB::table('credit_purchases')
                ->when(! $isSingleGroup, fn($q) => $q->whereBetween('payDate', [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'purchase_id', 'payDate', 'payment_total')
                ->get();

            foreach ($credits as $cp) {
                $activities->push([
                    'date'      => Carbon::parse($cp->payDate)->format('d-m-Y H:i'),
                    'type'      => 'Pembayaran Kredit Pembelian #' . $cp->purchase_id,
                    'raw_type'  => 'credit_purchase',
                    'id'        => $cp->id,
                    'amount'    => -$cp->payment_total, // negatif untuk cash-out
                    'direction' => 'out',
                ]);
            }

            $grouped = $credits
                ->groupBy(
                    fn($c) => $isSingleGroup
                        ? 'Total'
                        : Carbon::parse($c->payDate)
                        ->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y')
                )
                ->map(fn($group) => -$group->sum('payment_total')); // negatif

            $cashOuts = $cashOuts->mergeRecursive(
                $grouped->mapWithKeys(fn($v, $k) => [$k => ['credit_purchases' => $v]])
            );
        }

        //
        // 5) RETUR PELANGGAN (CASH-OUT; gunakan refund_amount)
        //
        if ($cashType !== 'in' && ($activity === 'other' || $activity === 'all')) {
            $customerReturns = DB::table('returs')
                ->where('return_type', 'customer')
                ->when(! $isSingleGroup, fn($q) => $q->whereBetween('return_date', [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'return_date', 'refund_amount')
                ->get();

            foreach ($customerReturns as $retur) {
                $activities->push([
                    'date'      => Carbon::parse($retur->return_date)->format('d-m-Y H:i'),
                    'type'      => 'Retur Pelanggan #' . $retur->id,
                    'raw_type'  => 'customer_return',
                    'id'        => $retur->id,
                    'amount'    => -$retur->refund_amount, // negatif karena keluar
                    'direction' => 'out',
                ]);
            }

            $grouped = $customerReturns
                ->groupBy(
                    fn($r) => $isSingleGroup
                        ? 'Total'
                        : Carbon::parse($r->return_date)
                        ->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y')
                )
                ->map(fn($group) => -$group->sum('refund_amount')); // negatif

            $cashOuts = $cashOuts->mergeRecursive(
                $grouped->mapWithKeys(fn($v, $k) => [$k => ['customer_returns' => $v]])
            );
        }

        //
        // 6) RETUR SUPPLIER (CASH-IN; gunakan refund_amount)
        //
        if ($cashType !== 'out' && ($activity === 'other' || $activity === 'all')) {
            $supplierReturns = DB::table('returs')
                ->where('return_type', 'supplier')
                ->when(! $isSingleGroup, fn($q) => $q->whereBetween('return_date', [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'return_date', 'refund_amount')
                ->get();

            foreach ($supplierReturns as $retur) {
                $activities->push([
                    'date'      => Carbon::parse($retur->return_date)->format('d-m-Y H:i'),
                    'type'      => 'Retur Supplier #' . $retur->id,
                    'raw_type'  => 'supplier_return',
                    'id'        => $retur->id,
                    'amount'    => $retur->refund_amount,
                    'direction' => 'in',
                ]);
            }

            $grouped = $supplierReturns
                ->groupBy(
                    fn($r) => $isSingleGroup
                        ? 'Total'
                        : Carbon::parse($r->return_date)
                        ->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y')
                )
                ->map(fn($group) => $group->sum('refund_amount'));

            $cashIns = $cashIns->mergeRecursive(
                $grouped->mapWithKeys(fn($v, $k) => [$k => ['supplier_returns' => $v]])
            );
        }

        //
        // 7) Siapkan array “data” untuk chart: tetap dikelompokkan per periode
        //
        if ($isSingleGroup) {
            $result = collect([
                [
                    'period'    => 'Total',
                    'cash_in'   => $cashIns->get('Total', []),
                    'cash_out'  => $cashOuts->get('Total', []),
                ]
            ]);
        } else {
            $allPeriods = collect();
            $current    = $start->copy();
            while ($current <= $end) {
                $allPeriods->push(
                    $current->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y')
                );
                $current->add($groupBy === 'month' ? '1 month' : '1 day');
            }

            $result = $allPeriods->map(fn($period) => [
                'period'    => $period,
                'cash_in'   => $cashIns->get($period, []),
                'cash_out'  => $cashOuts->get($period, []),
            ]);
        }

        //
        // 8) Urutkan aktivitas berdasarkan datetime (timestamp)
        //
        $activitiesSorted = $activities
            ->sortBy(fn($a) => Carbon::createFromFormat('d-m-Y H:i', $a['date'])->timestamp)
            ->values();

        if ($request->wantsJson() || $request->format === 'json') {
            return response()->json([
                'group_by'   => $groupBy,
                'data'       => $result->values(),
                'activities' => $activitiesSorted,
            ]);
        }

        return view('report.salesIndex', [
            'group_by'   => $groupBy,
            'data'       => $result->values(),
            'activities' => $activitiesSorted,
            'start'      => $start,
            'end'        => $end,
            'activity'   => $activity,
            'cash_type'  => $cashType,
        ]);
    }


    public function exportCashflowDetail(Request $request)
    {
        $start = $request->start;
        $end   = $request->end;

        $activities = $request->activity ?? ['all'];

        return Excel::download(new CashflowDetailExport($start, $end, $activities), 'laporan_arus_kas.xlsx');
    }
}
