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
        //
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
            'payDate'        => ['required', 'date'],
            'payment_total'  => ['required', 'numeric', 'min:1'],
            'description'    => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($purchase, $payload) {

            // Lock parent row to avoid race conditions
            $purchase->lockForUpdate();

            $remaining = $purchase->total - $purchase->prePaid;
            if ($payload['payment_total'] > $remaining) {
                abort(422, 'Nominal pembayaran melebihi sisa tagihan.');
            }

            $purchase->creditPurchase()->create($payload);

            // Update prepaid on the parent purchase
            $purchase->increment('prePaid', $payload['payment_total']);

            // Set status to "paid" if fully settled
            if ($purchase->prePaid >= $purchase->total) {
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
