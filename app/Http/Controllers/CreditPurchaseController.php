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
        // Validasi input
        $payload = $request->validate([
            'payDate' => ['required', 'date_format:Y-m-d\TH:i'],
            'payment_total' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            // Kunci baris purchase
            $purchase->lockForUpdate();

            // Hitung total retur
            $totalReturNominal = $purchase
                ->returs()
                ->with('items')
                ->get()
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            $initialPaid = $purchase->prePaid;
            $creditPaidSoFar = $purchase
                ->creditPurchase()
                ->sum('payment_total');

            $alreadyPaid = $initialPaid + $creditPaidSoFar;
            $effectiveTotal = $purchase->total - $totalReturNominal;
            $remainingBefore = $effectiveTotal - $alreadyPaid;

            // Validasi tidak overpay
            if ($payload['payment_total'] > $remainingBefore) {
                abort(422, 'Nominal pembayaran melebihi sisa tagihan setelah retur.');
            }

            // Simpan pembayaran baru
            $purchase->creditPurchase()->create($payload);

            // Update status jika sudah lunas
            $newCreditPaid = $creditPaidSoFar + $payload['payment_total'];
            $newAlreadyPaid = $initialPaid + $newCreditPaid;

            if ($newAlreadyPaid >= $effectiveTotal) {
                $purchase->status = 'paid';
                $purchase->save();
            }

            return back()->with('success', 'Pembayaran berhasil disimpan.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Tangani error database
            return back()
                ->withInput()
                ->withErrors(['db_error' => 'Gagal menyimpan pembayaran: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            // Tangani error umum
            return back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan, silakan coba lagi.']);
        }
    }

    public function bulkStore(Request $request)
    {
        $payload = $request->validate([
            'supplier_id'    => ['required', 'exists:suppliers,id'],
            'payDate'        => ['required', 'date_format:Y-m-d\TH:i'],
            'payment_total'  => ['required', 'numeric', 'min:1'],
            'description'    => ['nullable', 'string'],
        ]);

        // 1) Hitung total sisa hutang supplier
        $allUnpaid = Purchase::where('supplier_id', $payload['supplier_id'])
            ->where('status', 'unpaid')
            ->with(['returs.items', 'creditPurchase'])
            ->get();

        $aggregateRemaining = $allUnpaid->sum(function ($pur) {
            $totalRetur = $pur->returs
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            $effectiveTotal   = $pur->total - $totalRetur;
            $paidSoFar        = $pur->prePaid + $pur->creditPurchase->sum('payment_total');
            return max(0, $effectiveTotal - $paidSoFar);
        });

        // 2) Cegah kalau bayar melebihi total sisa
        if ($payload['payment_total'] > $aggregateRemaining) {
            abort(422, 'Nominal pembayaran melebihi total sisa hutang supplier.');
        }

        $amountToAllocate = $payload['payment_total'];

        try {
            foreach ($allUnpaid->sortBy('purchase_at') as $pur) {
                if ($amountToAllocate <= 0) {
                    break;
                }

                // lock baris purchase
                $pur = Purchase::whereKey($pur->id)
                    ->lockForUpdate()
                    ->with(['returs.items', 'creditPurchase'])
                    ->first();

                // recompute exactly like store()
                $totalReturNominal = $pur->returs
                    ->flatMap(fn($r) => $r->items)
                    ->sum('subtotal');

                $initialPaid      = $pur->prePaid;
                $creditPaidSoFar  = $pur->creditPurchase->sum('payment_total');
                $alreadyPaid      = $initialPaid + $creditPaidSoFar;
                $effectiveTotal   = $pur->total - $totalReturNominal;
                $remainingBefore  = $effectiveTotal - $alreadyPaid;

                if ($remainingBefore <= 0) {
                    continue;
                }

                // alokasikan sejumlah mungkin
                $alloc = min($remainingBefore, $amountToAllocate);

                if ($alloc > $remainingBefore) {
                    abort(422, "Nominal pembayaran melebihi sisa tagihan pembelian #{$pur->id}.");
                }

                // simpan pembayaran
                $pur->creditPurchase()->create([
                    'payDate'       => $payload['payDate'],
                    'payment_total' => $alloc,
                    'description'   => $payload['description'] ?? null,
                ]);

                // update status jika lunas
                $newCreditPaid  = $creditPaidSoFar + $alloc;
                $newAlreadyPaid = $initialPaid + $newCreditPaid;
                if ($newAlreadyPaid >= $effectiveTotal) {
                    $pur->status = 'paid';
                    $pur->save();
                }

                $amountToAllocate -= $alloc;
            }

            return back()->with('success', 'Pembayaran bulk pembelian berhasil disimpan.');
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
