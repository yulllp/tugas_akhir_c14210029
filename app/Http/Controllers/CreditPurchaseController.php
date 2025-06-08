<?php

namespace App\Http\Controllers;

use App\Models\CreditPurchase;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('credit.indexSupplier', [
            'title' => 'Utang Supplier',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Purchase $purchase)
    {
        $payload = $request->validate([
            'payDate' => ['required', 'date_format:Y-m-d\TH:i'],
            'payment_total' => ['required', 'numeric', 'min:1'],
            'description'   => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($purchase, $payload) {
            $purchase->lockForUpdate();

            $totalReturNominal = $purchase
                ->returs()
                ->with('items')
                ->get()
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            $initialPaid     = $purchase->prePaid;
            $creditPaidSoFar = $purchase
                ->creditPurchase()
                ->sum('payment_total');

            $alreadyPaid = $initialPaid + $creditPaidSoFar;

            $effectiveTotal = $purchase->total - $totalReturNominal;

            $remainingBeforeThisPayment = $effectiveTotal - $alreadyPaid;

            if ($payload['payment_total'] > $remainingBeforeThisPayment) {
                abort(422, 'Nominal pembayaran melebihi sisa tagihan setelah retur.');
            }

            $purchase->creditPurchase()->create($payload);

            $newCreditPaidSoFar = $creditPaidSoFar + $payload['payment_total'];
            $newAlreadyPaid     = $initialPaid + $newCreditPaidSoFar;

            if ($newAlreadyPaid >= $effectiveTotal) {
                $purchase->status = 'paid';
                $purchase->save();
            }
        });

        return back()->with('success', 'Pembayaran berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditPurchase $creditPurchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CreditPurchase $creditPurchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CreditPurchase $creditPurchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CreditPurchase $creditPurchase)
    {
        //
    }
}
