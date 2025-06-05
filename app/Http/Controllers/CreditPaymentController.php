<?php

namespace App\Http\Controllers;

use App\Models\CreditPayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('credit.indexCustomer', [
            'title' => 'Utang Pelanggan',
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
    public function store(Request $request, Transaction $transaction)
    {
        $payload = $request->validate([
            'payDate'        => ['required', 'date'],
            'payment_total'  => ['required', 'numeric', 'min:1'],
            'description'    => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($transaction, $payload) {

            // Lock parent row to avoid race conditions
            $transaction->lockForUpdate();

            $remaining = $transaction->total - $transaction->prePaid;
            if ($payload['payment_total'] > $remaining) {
                abort(422, 'Nominal pembayaran melebihi sisa tagihan.');
            }

            $transaction->creditPayment()->create($payload);

            // Update prepaid on the parent transaction
            $transaction->increment('prePaid', $payload['payment_total']);

            // Set status to "paid" if fully settled
            if ($transaction->prePaid >= $transaction->total) {
                $transaction->status = 'paid';
                $transaction->save();
            }
        });

        return back()->with('success', 'Pembayaran berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditPayment $creditPayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CreditPayment $creditPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CreditPayment $creditPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CreditPayment $creditPayment)
    {
        //
    }
}
