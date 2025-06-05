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

        if (!$isSingleGroup) {
            $start = Carbon::parse($request->start, 'Asia/Jayapura')->startOfDay();
            $end = Carbon::parse($request->end, 'Asia/Jayapura')->endOfDay();

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

        $diffDays = !$isSingleGroup ? $start->diffInDays($end) : 0;
        $groupBy = $isSingleGroup ? 'total' : ($diffDays > 45 ? 'month' : 'date');
        $format = $groupBy === 'month' ? 'MM-YYYY' : 'DD-MM-YYYY';

        $cashIns = collect();
        $cashOuts = collect();
        $activities = collect();

        if ($cashType !== 'out' && ($activity === 'transaction' || $activity === 'all')) {
            $transactions = DB::table('transactions')
                ->leftJoin('credit_payments', 'credit_payments.transaction_id', '=', 'transactions.id')
                ->when(!$isSingleGroup, fn($q) => $q->whereBetween('transactions.transaction_at', [$start, $end]))
                ->whereNull('transactions.deleted_at')
                ->select('transactions.id', 'transactions.transaction_at', 'transactions.prePaid', DB::raw('COALESCE(credit_payments.payment_total, 0) as credit_paid'))
                ->get();

            foreach ($transactions as $tx) {
                $amount = $tx->prePaid - $tx->credit_paid;
                $activities->push([
                    'date' => Carbon::parse($tx->transaction_at)->format('Y-m-d'),
                    'type' => 'Transaksi #' . $tx->id,
                    'raw_type' => 'transaction',
                    'id' => $tx->id,
                    'amount' => $amount,
                    'direction' => 'in',
                ]);
            }

            $grouped = $transactions->groupBy(fn($tx) => $isSingleGroup ? 'Total' : Carbon::parse($tx->transaction_at)->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->prePaid - $tx->credit_paid));

            $cashIns = $cashIns->mergeRecursive($grouped->mapWithKeys(fn($v, $k) => [$k => ['transactions' => $v]]));
        }

        if ($cashType !== 'out' && ($activity === 'transaction' || $activity === 'all')) {
            $credits = DB::table('credit_payments')
                ->when(!$isSingleGroup, fn($q) => $q->whereBetween(DB::raw('"payDate"'), [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'transaction_id', DB::raw('"payDate"'), 'payment_total')
                ->get();

            foreach ($credits as $cr) {
                $activities->push([
                    'date' => Carbon::parse($cr->payDate)->format('Y-m-d'),
                    'type' => 'Pembayaran Kredit Transaksi #' . $cr->transaction_id,
                    'raw_type' => 'credit_payment',
                    'id' => $cr->id,
                    'amount' => $cr->payment_total,
                    'direction' => 'in',
                ]);
            }

            $grouped = $credits->groupBy(fn($c) => $isSingleGroup ? 'Total' : Carbon::parse($c->payDate)->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y'))
                ->map(fn($group) => $group->sum('payment_total'));

            $cashIns = $cashIns->mergeRecursive($grouped->mapWithKeys(fn($v, $k) => [$k => ['credit_payments' => $v]]));
        }

        if ($cashType !== 'in' && ($activity === 'purchasing' || $activity === 'all')) {
            $purchases = DB::table('purchases')
                ->leftJoin('credit_purchases', 'credit_purchases.purchase_id', '=', 'purchases.id')
                ->when(!$isSingleGroup, fn($q) => $q->whereBetween('buyDate', [$start, $end]))
                ->whereNull('purchases.deleted_at')
                ->select('purchases.id', 'purchases.buyDate', 'purchases.prePaid', DB::raw('COALESCE(credit_purchases.payment_total, 0) as credit_paid'))
                ->get();

            foreach ($purchases as $p) {
                $amount = $p->prePaid - $p->credit_paid;
                $activities->push([
                    'date' => Carbon::parse($p->buyDate)->format('Y-m-d'),
                    'type' => 'Pembelian #' . $p->id,
                    'raw_type' => 'purchase',
                    'id' => $p->id,
                    'amount' => $amount,
                    'direction' => 'out',
                ]);
            }

            $grouped = $purchases->groupBy(fn($p) => $isSingleGroup ? 'Total' : Carbon::parse($p->buyDate)->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y'))
                ->map(fn($group) => $group->sum(fn($p) => $p->prePaid - $p->credit_paid));

            $cashOuts = $cashOuts->mergeRecursive($grouped->mapWithKeys(fn($v, $k) => [$k => ['purchases' => $v]]));
        }

        if ($cashType !== 'in' && ($activity === 'purchasing' || $activity === 'all')) {
            $credits = DB::table('credit_purchases')
                ->when(!$isSingleGroup, fn($q) => $q->whereBetween(DB::raw('"payDate"'), [$start, $end]))
                ->whereNull('deleted_at')
                ->select('id', 'purchase_id', DB::raw('"payDate"'), 'payment_total')
                ->get();

            foreach ($credits as $cp) {
                $activities->push([
                    'date' => Carbon::parse($cp->payDate)->format('Y-m-d'),
                    'type' => 'Pembayaran Kredit Pembelian #' . $cp->purchase_id,
                    'raw_type' => 'credit_purchase',
                    'id' => $cp->id,
                    'amount' => $cp->payment_total,
                    'direction' => 'out',
                ]);
            }

            $grouped = $credits->groupBy(fn($c) => $isSingleGroup ? 'Total' : Carbon::parse($c->payDate)->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y'))
                ->map(fn($group) => $group->sum('payment_total'));

            $cashOuts = $cashOuts->mergeRecursive($grouped->mapWithKeys(fn($v, $k) => [$k => ['credit_purchases' => $v]]));
        }

        if ($cashType !== 'in' && ($activity === 'other' || $activity === 'all')) {
            $customerReturns = DB::table('returs')
                ->join('retur_items', 'retur_items.retur_id', '=', 'returs.id')
                ->where('return_type', 'customer')
                ->when(!$isSingleGroup, fn($q) => $q->whereBetween('return_date', [$start, $end]))
                ->whereNull('returs.deleted_at')
                ->select('returs.id', 'return_date', DB::raw('SUM(retur_items.subtotal) as subtotal'))
                ->groupBy('returs.id', 'return_date')
                ->get();

            foreach ($customerReturns as $retur) {
                $activities->push([
                    'date' => Carbon::parse($retur->return_date)->format('Y-m-d'),
                    'type' => 'Retur Pelanggan #' . $retur->id,
                    'raw_type' => 'customer_return',
                    'id' => $retur->id,
                    'amount' => $retur->subtotal,
                    'direction' => 'out',
                ]);
            }

            $grouped = $customerReturns->groupBy(fn($r) => $isSingleGroup ? 'Total' : Carbon::parse($r->return_date)->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y'))
                ->map(fn($group) => $group->sum('subtotal'));

            $cashOuts = $cashOuts->mergeRecursive($grouped->mapWithKeys(fn($v, $k) => [$k => ['customer_returns' => $v]]));
        }

        if ($cashType !== 'out' && ($activity === 'other' || $activity === 'all')) {
            $supplierReturns = DB::table('returs')
                ->join('retur_items', 'retur_items.retur_id', '=', 'returs.id')
                ->where('return_type', 'supplier')
                ->when(!$isSingleGroup, fn($q) => $q->whereBetween('return_date', [$start, $end]))
                ->whereNull('returs.deleted_at')
                ->select('returs.id', 'return_date', DB::raw('SUM(retur_items.subtotal) as subtotal'))
                ->groupBy('returs.id', 'return_date')
                ->get();

            foreach ($supplierReturns as $retur) {
                $activities->push([
                    'date' => Carbon::parse($retur->return_date)->format('Y-m-d'),
                    'type' => 'Retur Supplier #' . $retur->id,
                    'raw_type' => 'supplier_return',
                    'id' => $retur->id,
                    'amount' => $retur->subtotal,
                    'direction' => 'in',
                ]);
            }

            $grouped = $supplierReturns->groupBy(fn($r) => $isSingleGroup ? 'Total' : Carbon::parse($r->return_date)->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y'))
                ->map(fn($group) => $group->sum('subtotal'));

            $cashIns = $cashIns->mergeRecursive($grouped->mapWithKeys(fn($v, $k) => [$k => ['supplier_returns' => $v]]));
        }

        if ($isSingleGroup) {
            $result = collect([
                [
                    'period' => 'Total',
                    'cash_in' => $cashIns->get('Total', []),
                    'cash_out' => $cashOuts->get('Total', []),
                ]
            ]);
        } else {
            $allPeriods = collect();
            $current = $start->copy();
            while ($current <= $end) {
                $allPeriods->push($current->format($groupBy === 'month' ? 'm-Y' : 'd-m-Y'));
                $current->add($groupBy === 'month' ? '1 month' : '1 day');
            }

            $result = $allPeriods->map(function ($period) use ($cashIns, $cashOuts) {
                return [
                    'period' => $period,
                    'cash_in' => $cashIns->get($period, []),
                    'cash_out' => $cashOuts->get($period, []),
                ];
            });
        }

        if ($request->wantsJson() || $request->format === 'json') {
            return response()->json([
                'group_by' => $groupBy,
                'data' => $result->values(),
                'activities' => $activities->sortBy('date')->values(),
            ]);
        } else {
            return view('report.salesIndex', [
                'group_by' => $groupBy,
                'data' => $result->values(),
                'activities' => $activities->sortBy('date')->values(),
                'start' => $start,
                'end' => $end,
                'activity' => $activity,
                'cash_type' => $cashType,
            ]);
        }
    }


    public function exportCashflowDetail(Request $request)
    {
        $start = $request->start;
        $end   = $request->end;

        $activities = $request->activity ?? ['all'];

        return Excel::download(new CashflowDetailExport($start, $end, $activities), 'laporan_arus_kas.xlsx');
    }
}
