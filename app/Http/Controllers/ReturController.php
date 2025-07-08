<?php

namespace App\Http\Controllers;

use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\Purchase;
use App\Models\Retur;
use App\Models\ReturItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('retur.indexCust', [
            'title' => 'Retur',
        ]);
    }

    public function index2()
    {
        return view('retur.indexSupp', [
            'title' => 'Retur',
        ]);
    }

    public function createTransaction(Transaction $transaction)
    {
        $transaction->load('detailTransactions');
        return view('retur.returTransaction', compact('transaction'));
    }

    public function createPurchase(Purchase $purchase)
    {
        $purchase->load('productPurchase');
        return view('retur.returPurchase', compact('purchase'));
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
    public function storeTransaction(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'return_type' => 'required|in:customer',
            'items' => 'required|array|min:1',
        ]);

        // DB::beginTransaction();
        try {
            $transaction = Transaction::with('detailTransactions', 'returs.items')->findOrFail($request->transaction_id);

            $retur = Retur::create([
                'transaction_id' => $transaction->id,
                'return_date' => Carbon::now(),
                'description' => $request->description,
                'return_type' => 'customer',
                'user_id' => Auth::id(),
                'refund_amount' => 0,
            ]);

            $thisReturnNominal = 0;

            foreach ($request->items as $detail_id => $data) {
                $detail = DetailTransaction::findOrFail($detail_id);
                $returnQty = intval($data['return_quantity'] ?? 0);
                $maxQty = $detail->qty - $detail->returnedQty();

                if ($returnQty <= 0) {
                    continue;
                }
                if ($returnQty > $maxQty) {
                    throw new \Exception("Qty retur untuk produk {$detail->product->name} melebihi sisa yang bisa diretur.");
                }

                $price = $detail->productPrice->id;
                $disc = intval($data['disc'] ?? 0);
                $subtotal = $returnQty * ($detail->productPrice->sellPrice - $disc);

                ReturItem::create([
                    'retur_id' => $retur->id,
                    'product_id' => $data['product_id'],
                    'condition' => $data['condition'],
                    'note' => $data['note'] ?? null,
                    'qty' => $returnQty,
                    'product_price_id' => $price,
                    'buy_price' => null,
                    'disc' => $disc,
                    'subtotal' => $subtotal,
                ]);

                $thisReturnNominal += $subtotal;

                if ($data['condition'] === 'good') {
                    Product::where('id', $data['product_id'])->increment('totalStok', $returnQty);
                }
            }

            $previousReturNominal = $transaction
                ->returs()
                ->where('id', '<>', $retur->id)
                ->with('items')
                ->get()
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            $newTotalReturNominal = $previousReturNominal + $thisReturnNominal;
            $effectiveTotalAfterReturn = $transaction->total - $newTotalReturNominal;

            $initialPaid = $transaction->prePaid;
            $creditPaidSoFar = $transaction->creditPayment()->sum('payment_total');
            $alreadyPaid = $initialPaid + $creditPaidSoFar;

            $refundAmount = 0;
            if ($alreadyPaid > $effectiveTotalAfterReturn) {
                $refundAmount = $alreadyPaid - $effectiveTotalAfterReturn;
            }

            $retur->refund_amount = $refundAmount;
            $retur->save();

            if ($alreadyPaid >= $effectiveTotalAfterReturn) {
                $transaction->status = 'paid';
                $transaction->save();
            }

            // DB::commit();

            activity('retur')
                ->performedOn($retur)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id_retur' => $retur->id,
                    'tipe' => 'customer',
                    'transaction_id' => $retur->transaction_id,
                    'jumlah_item' => $retur->items->sum('qty'),
                    'total_retur' => $retur->items->sum('subtotal'),
                    'refund' => $retur->refund_amount,
                ])
                ->log("Retur customer #{$retur->id} berhasil dibuat");

            return redirect()->route('transactions.show', $transaction->id)
                ->with('success', 'Retur berhasil disimpan. Refund sebesar Rp ' . number_format($refundAmount, 0, ',', '.') . ' telah dihitung.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage());
        }
    }

    public function storePurchase(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'return_type' => 'required|in:supplier',
            'items' => 'required|array|min:1',
        ]);

        // DB::beginTransaction();
        try {
            $purchase = Purchase::with('productPurchase', 'returs.items')->findOrFail($request->purchase_id);

            $retur = Retur::create([
                'purchase_id' => $purchase->id,
                'return_date' => Carbon::now(),
                'description' => $request->description,
                'return_type' => 'supplier',
                'user_id' => Auth::id(),
                'refund_amount' => 0,
            ]);


            $thisReturnNominal = 0;
            foreach ($request->items as $detail_id => $data) {
                $detail = ProductPurchase::findOrFail($detail_id);
                $returnQty = intval($data['return_quantity'] ?? 0);
                $maxQty = $detail->qty - $detail->returnedQty();

                if ($returnQty <= 0) {
                    continue;
                }
                if ($returnQty > $maxQty) {
                    throw new \Exception("Qty retur untuk produk {$detail->product->name} melebihi sisa yang bisa diretur.");
                }

                $price = $detail->buyPrice;
                $productId = $data['product_id'];
                $condition = $data['condition'] ?? 'good';
                $subtotal = $returnQty * $price;

                $product = Product::findOrFail($productId);
                if ($condition === 'good' && $product->totalStok < $returnQty) {
                    throw new \Exception("Stok produk {$product->name} tidak mencukupi untuk retur.");
                }

                ReturItem::create([
                    'retur_id' => $retur->id,
                    'product_id' => $productId,
                    'condition' => $condition,
                    'note' => $data['note'] ?? null,
                    'qty' => $returnQty,
                    'product_price_id' => null,
                    'buy_price' => $price,
                    'disc' => 0,
                    'subtotal' => $subtotal,
                ]);

                $thisReturnNominal += $subtotal;

                if ($condition === 'good') {
                    $product->decrement('totalStok', $returnQty);
                }
            }

            $previousReturNominal = $purchase
                ->returs()
                ->where('id', '<>', $retur->id)
                ->with('items')
                ->get()
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            $newTotalReturNominal = $previousReturNominal + $thisReturnNominal;
            $effectiveTotalAfterReturn = $purchase->total - $newTotalReturNominal;

            $initialPaid = $purchase->prePaid;
            $creditPaidSoFar = $purchase->creditPurchase()->sum('payment_total');
            $alreadyPaid = $initialPaid + $creditPaidSoFar;

            $refundAmount = 0;
            if ($alreadyPaid > $effectiveTotalAfterReturn) {
                $refundAmount = $alreadyPaid - $effectiveTotalAfterReturn;
            }

            $retur->refund_amount = $refundAmount;
            $retur->save();

            if ($alreadyPaid >= $effectiveTotalAfterReturn) {
                $purchase->status = 'paid';
                $purchase->save();
            }

            // DB::commit();

            activity('retur')
                ->performedOn($retur)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id_retur' => $retur->id,
                    'tipe' => 'supplier',
                    'purchase_id' => $retur->purchase_id,
                    'jumlah_item' => $retur->items->sum('qty'),
                    'total_retur' => $retur->items->sum('subtotal'),
                    'refund' => $retur->refund_amount,
                ])
                ->log("Retur supplier #{$retur->id} berhasil dibuat");

            return redirect()
                ->route('purchases.show', $purchase->id)
                ->with('success', 'Retur pembelian berhasil disimpan. Refund sebesar Rp ' . number_format($refundAmount, 0, ',', '.') . ' telah dihitung.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage());
        }
    }




    /**
     * Display the specified resource.
     */
    public function show(Retur $retur)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Retur $retur)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Retur $retur)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Retur $retur)
    {
        //
    }
}
