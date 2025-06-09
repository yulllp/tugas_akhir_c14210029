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
            'payDate' => ['required', 'date_format:Y-m-d\TH:i'],
            'payment_total' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $transaction->lockForUpdate();

            $totalReturNominal = $transaction
                ->returs()
                ->with('items')
                ->get()
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            $initialPaid = $transaction->prePaid;
            $creditPaidSoFar = $transaction
                ->creditPayment()
                ->sum('payment_total');

            $alreadyPaid = $initialPaid + $creditPaidSoFar;
            $effectiveTotal = $transaction->total - $totalReturNominal;
            $remainingBefore = $effectiveTotal - $alreadyPaid;

            if ($payload['payment_total'] > $remainingBefore) {
                abort(422, 'Nominal pembayaran melebihi sisa tagihan setelah retur.');
            }

            $transaction->creditPayment()->create($payload);

            $newCreditPaid = $creditPaidSoFar + $payload['payment_total'];
            $newAlreadyPaid = $initialPaid + $newCreditPaid;

            if ($newAlreadyPaid >= $effectiveTotal) {
                $transaction->status = 'paid';
                $transaction->save();
            }

            return back()->with('success', 'Pembayaran berhasil disimpan.');

        } catch (\Illuminate\Database\QueryException $e) {
            return back()
                ->withInput()
                ->withErrors(['db_error' => 'Gagal menyimpan pembayaran: ' . $e->getMessage()]);

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan, silakan coba lagi.']);
        }
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
