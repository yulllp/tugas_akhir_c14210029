<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $todayStart = Carbon::now()->startOfDay();
        $todayEnd   = Carbon::now()->endOfDay();

        $itemsSoldToday = DB::table('detail_transactions')
            ->join('transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.transaction_at', [$todayStart, $todayEnd])
            ->selectRaw('COALESCE(SUM(detail_transactions.qty), 0) as total_qty')
            ->value('total_qty');

        $salesCashIn = DB::table('transactions')
            ->whereBetween('transaction_at', [$todayStart, $todayEnd])
            ->whereNull('deleted_at')
            ->sum('prePaid');

        $creditPaymentsCashIn = DB::table('credit_payments')
            ->whereBetween('payDate', [$todayStart, $todayEnd])
            ->whereNull('deleted_at')
            ->sum('payment_total');

        $supplierReturnsCashIn = DB::table('returs')
            ->where('return_type', 'supplier')
            ->whereBetween('return_date', [$todayStart, $todayEnd])
            ->whereNull('deleted_at')
            ->sum('refund_amount');

        $revenueToday = $salesCashIn 
                      + $creditPaymentsCashIn 
                      + $supplierReturnsCashIn;

        $totalCustomers = DB::table('customers')->count();

        $topProducts = DB::table('detail_transactions')
            ->join('transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->join('products', 'detail_transactions.product_id', '=', 'products.id')
            ->whereBetween('transactions.transaction_at', [$todayStart, $todayEnd])
            ->whereNull('transactions.deleted_at')
            ->select(
                'products.id',
                'products.name as product_name',
                DB::raw('SUM(detail_transactions.qty) as total_sold')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return view('home.index', [
            'itemsSoldToday'   => $itemsSoldToday,
            'revenueToday'     => $revenueToday,
            'totalCustomers'   => $totalCustomers,
            'topProducts'      => $topProducts,
        ]);
    }
}
