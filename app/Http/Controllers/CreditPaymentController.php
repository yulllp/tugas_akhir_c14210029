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

    public function remaining(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);

        $customerId = $request->customer_id;

        $unpaid = Transaction::with(['returs.items', 'creditPayment'])
            ->where('status', 'unpaid')
            ->where('customer_id', $customerId)
            ->get();

        $totalRemaining = $unpaid->reduce(function ($carry, $trx) {
            // compute netTotal after retur
            $returTotal = $trx->returs->flatMap->items->sum('subtotal');
            $netTotal   = max(0, $trx->total - $returTotal);

            // already paid
            $paid = $trx->prePaid + $trx->creditPayment->sum('payment_total');
            $paid = max(0, $paid);

            // remaining per trx
            $rem = max(0, $netTotal - $paid);
            return $carry + $rem;
        }, 0);

        return response()->json([
            'remaining' => $totalRemaining,
        ]);
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

    public function bulkStore(Request $request)
    {
        $payload = $request->validate([
            'customer_id'    => ['required', 'exists:customers,id'],
            'payDate'        => ['required', 'date_format:Y-m-d\TH:i'],
            'payment_total'  => ['required', 'numeric', 'min:1'],
            'description'    => ['nullable', 'string'],
        ]);

        // 1) Calculate the customer's total remaining debt
        $allUnpaid = Transaction::where('customer_id', $payload['customer_id'])
            ->where('status', 'unpaid')
            ->with(['returs.items', 'creditPayment'])
            ->get();

        $aggregateRemaining = $allUnpaid->sum(function ($trx) {
            $totalReturNominal = $trx
                ->returs
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            $effectiveTotal = $trx->total - $totalReturNominal;
            $creditPaidSoFar = $trx->prePaid + $trx->creditPayment->sum('payment_total');
            return max(0, $effectiveTotal - $creditPaidSoFar);
        });

        // 2) Ensure you’re not over‐paying in total
        if ($payload['payment_total'] > $aggregateRemaining) {
            abort(422, 'Nominal pembayaran melebihi total sisa tagihan pelanggan.');
        }

        $amountToAllocate = $payload['payment_total'];

        try {
            foreach ($allUnpaid->sortBy('transaction_at') as $trx) {
                if ($amountToAllocate <= 0) {
                    break;
                }

                // Lock *this* transaction for update (just like your single‐store)
                $trx = Transaction::whereKey($trx->id)
                    ->lockForUpdate()
                    ->with(['returs.items', 'creditPayment'])
                    ->first();

                // Recompute exactly as in store():
                $totalReturNominal = $trx
                    ->returs
                    ->flatMap(fn($r) => $r->items)
                    ->sum('subtotal');

                $initialPaid       = $trx->prePaid;
                $creditPaidSoFar   = $trx->creditPayment->sum('payment_total');
                $alreadyPaid       = $initialPaid + $creditPaidSoFar;
                $effectiveTotal    = $trx->total - $totalReturNominal;
                $remainingBefore   = $effectiveTotal - $alreadyPaid;

                if ($remainingBefore <= 0) {
                    continue;
                }

                // allocate up to remainingBefore
                $alloc = min($remainingBefore, $amountToAllocate);

                // same over‐pay guard (should never trigger given step 2)
                if ($alloc > $remainingBefore) {
                    abort(422, "Nominal pembayaran melebihi sisa tagihan transaksi #{$trx->id}.");
                }

                // create payment
                $trx->creditPayment()->create([
                    'payDate'       => $payload['payDate'],
                    'payment_total' => $alloc,
                    'description'   => $payload['description'] ?? null,
                ]);

                // status update, exactly like store()
                $newCreditPaid  = $creditPaidSoFar + $alloc;
                $newAlreadyPaid = $initialPaid + $newCreditPaid;
                if ($newAlreadyPaid >= $effectiveTotal) {
                    $trx->status = 'paid';
                    $trx->save();
                }

                $amountToAllocate -= $alloc;
            }

            return back()->with('success', 'Pembayaran bulk berhasil disimpan.');
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
