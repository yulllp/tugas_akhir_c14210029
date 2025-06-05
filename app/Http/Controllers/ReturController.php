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
        return view('retur.index', [
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

        DB::beginTransaction();
        try {
            $transaction = Transaction::with('detailTransactions')->findOrFail($request->transaction_id);

            // Create the return record
            $retur = Retur::create([
                'transaction_id' => $transaction->id,
                'return_date' => Carbon::now(),
                'description' => $request->description,
                'return_type' => 'customer',
                'user_id' => Auth::id(),
            ]);

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

                // Optional: Update stock if the returned item is in good condition
                if ($data['condition'] === 'good') {
                    Product::where('id', $data['product_id'])->increment('totalStok', $returnQty);
                }
            }

            DB::commit();
            return redirect()->route('transactions.show', $transaction->id)->with('success', 'Retur berhasil disimpan.');
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

        DB::beginTransaction();
        try {
            $purchase = Purchase::with('productPurchase')->findOrFail($request->purchase_id);

            // Create the return record
            $retur = Retur::create([
                'purchase_id' => $purchase->id,
                'return_date' => Carbon::now(),
                'description' => $request->description,
                'return_type' => 'supplier',
                'user_id' => Auth::id(),
            ]);

            foreach ($request->items as $detail_id => $data) {
                $detail = ProductPurchase::findOrFail($detail_id);

                $returnQty = intval($data['return_quantity'] ?? 0);
                $maxQty = $detail->qty - $detail->returnedQty(); // Assuming you have returnedQty() on DetailPurchase

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

                if ($condition === 'good') {
                    $product->decrement('totalStok', $returnQty);
                }
            }

            DB::commit();
            return redirect()->route('purchases.show', $purchase->id)->with('success', 'Retur pembelian berhasil disimpan.');
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
