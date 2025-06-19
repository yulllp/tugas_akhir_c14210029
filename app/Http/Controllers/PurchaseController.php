<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPurchase;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\TempPurchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('purchase.index', [
            'title' => 'Pembelian',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::with('latestPrice')->get();
        $suppliers = Supplier::all();
        return view('purchase.create', [
            'title' => 'Pembelian',
            'products' => $products,
            'suppliers' => $suppliers,
        ]);
    }

    public function getTempPurchase()
    {
        $tempPurchases = TempPurchase::with(['product.latestPrice'])
            ->where('user_id', Auth::id())
            ->orderBy('id', 'asc')
            ->get();

        $formatted = $tempPurchases->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'price' => $item->product->latestPrice->sellPrice ?? 0,
                'buyPrice' => $item->buyPrice,
                'qty' => $item->qty,
                'expDate' => $item->expDate,
                'subtotal' => $item->subtotal,
            ];
        });

        return response()->json($formatted);
    }

    public function storeTemp(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'buyPrice' => 'required|numeric|min:0',
            'expDate' => 'nullable|date|after_or_equal:today',
        ]);

        $userId = Auth::id();

        // Prevent duplicate entry for same product and user
        $existing = TempPurchase::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Produk sudah ditambahkan.',
            ]);
        }

        $qty = $request->qty;
        $buyPrice = $request->buyPrice;
        $subtotal = $buyPrice * $qty;

        TempPurchase::create([
            'user_id' => $userId,
            'product_id' => $request->product_id,
            'qty' => $qty,
            'buyPrice' => $buyPrice,
            'subtotal' => $subtotal,
            'expDate' => $request->expDate,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
        ]);
    }

    public function deleteTemp(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:temp_purchases,id',
        ]);

        $deleted = TempPurchase::where('id', $request->id)->where('user_id', Auth::id())->delete();

        if ($deleted) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Item tidak ditemukan atau tidak dapat dihapus.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $final = json_decode($request->input('final_transaction'), true);

        if (!$final || !is_array($final)) {
            return redirect()
                ->route('purchases.create')
                ->with('error', 'Format pembelian tidak valid.');
        }

        $validated = Validator::make($final, [
            'faktur' => 'required|string',
            'total' => 'required|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'supplier_id' => 'required|exists:suppliers,id',
            'credit' => 'required|boolean',
            'shipping' => 'required|in:arrive,pending',
        ]);

        if ($validated->fails()) {
            return redirect()
                ->route('purchases.create')
                ->withErrors($validated)
                ->withInput();
        }

        $tempItems = TempPurchase::with('product')->where('user_id', Auth::id())->get();

        if ($tempItems->isEmpty()) {
            return redirect()
                ->route('purchases.create')
                ->with('error', 'Tidak ada item pembelian.');
        }

        // DB::beginTransaction();

        try {
            $entryDate = $final['shipping'] === 'arrive'
                ? now()
                : null;

            $purchase = Purchase::create([
                'user_id' => Auth::id(),
                'buyDate' => Carbon::now(),
                'supplier_id' => $final['supplier_id'],
                'faktur' => $final['faktur'],
                'total' => $final['total'],
                'prePaid' => $final['paid'],
                'status' => $final['credit'] ? 'unpaid' : 'paid',
                'shipping' => $final['shipping'],
                'entryDate' => $entryDate,
            ]);

            foreach ($tempItems as $temp) {
                $product = Product::find($temp->product_id);
                if (!$product) {
                    DB::rollBack();
                    return redirect()
                        ->route('purchases.create')
                        ->with('error', "Produk tidak ditemukan untuk ID: {$temp->product_id}");
                }

                $latestPrice = $product->latestPrice()->first();

                if (!$latestPrice) {
                    ProductPrice::create([
                        'product_id' => $product->id,
                        'sellPrice' => $temp->price,
                    ]);
                } else {
                    if ($temp->buyPrice > $latestPrice->sellPrice) {
                        $lastPurchase = $product->productPurchases()->latest()->first();

                        $newSellPrice = $temp->buyPrice;
                        if ($lastPurchase) {
                            $oldBuy = $lastPurchase->buyPrice;
                            $oldSell = $latestPrice->sellPrice;
                            $diff = $oldSell - $oldBuy;
                            $newSellPrice = $temp->buyPrice + $diff;
                        }

                        ProductPrice::create([
                            'product_id' => $product->id,
                            'sellPrice' => $newSellPrice,
                        ]);
                    }
                }

                ProductPurchase::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'qty' => $temp->qty,
                    'buyPrice' => $temp->buyPrice,
                    'subtotal' => $temp->subtotal,
                    'expDate' => $temp->expDate,
                ]);

                if ($final['shipping'] === 'arrive') {
                    $product->increment('totalStok', $temp->qty);
                }
            }

            TempPurchase::where('user_id', Auth::id())->delete();

            // DB::commit();

            activity('pembelian')
                ->performedOn($purchase)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id' => $purchase->id,
                    'faktur' => $purchase->faktur,
                    'total' => $purchase->total,
                    'dibayar' => $purchase->prePaid,
                    'supplier_id' => $purchase->supplier_id,
                    'status' => $purchase->status,
                    'shipping' => $purchase->shipping,
                    'tanggal_entry' => optional($purchase->entryDate)->format('d-m-Y H:i'),
                ])
                ->log("Pembelian #{$purchase->id} berhasil dibuat");

            return redirect()->route('purchases.create')->with('success', 'Pembelian berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('purchases.create')
                ->with('error', 'Terjadi kesalahan saat menyimpan pembelian: ' . $e->getMessage());
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $title = 'Detail Pembelian #' . $purchase->id;
        $purchase->load('productPurchase.product');
        return view('purchase.show', compact('purchase', 'title'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)   // ← route parameter is now the ID
    {
        $purchase = Purchase::with('productPurchase.product')->findOrFail($id);

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchase.edit', [
            'purchase' => $purchase,
            'suppliers' => $suppliers,
            'title' => 'Edit Pembelian #' . $purchase->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, int $id)
    {
        $purchase = Purchase::with('productPurchase')
            ->findOrFail($id);

        // Validasi input
        $validated = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'shipping' => 'required|in:arrive,pending',
            'fraktur' => 'required|string',
        ])->validate();

        $shippingWasPending = $purchase->shipping === 'pending';
        $shippingNowArrive = $validated['shipping'] === 'arrive';
        $shouldUpdateStock = $shippingWasPending && $shippingNowArrive;

        try {
            // Update data pembelian
            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                // only allow "shipping" to be set once to arrive
                'shipping' => $purchase->shipping === 'arrive'
                    ? 'arrive'
                    : $validated['shipping'],
                'entryDate' => $shouldUpdateStock
                    ? now()
                    : $purchase->entryDate,
                'faktur' => $validated['fraktur'],
            ]);

            // Jika status berubah dari pending ke arrive, perbarui stok produk
            if ($shouldUpdateStock) {
                foreach ($purchase->productPurchase as $item) {
                    $item->product()->increment('totalStok', $item->qty);
                }
            }

            // Log aktivitas
            activity('pembelian')
                ->performedOn($purchase)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id' => $purchase->id,
                    'lama' => [
                        'supplier_id' => $purchase->getOriginal('supplier_id'),
                        'shipping' => $purchase->getOriginal('shipping'),
                        'fraktur' => $purchase->getOriginal('fraktur'),
                    ],
                    'baru' => [
                        'supplier_id' => $validated['supplier_id'],
                        'shipping' => $purchase->shipping,
                        'fraktur' => $validated['fraktur'],
                    ],
                    'entryDate_lama' => optional($purchase->getOriginal('entryDate'))->format('d-m-Y H:i'),
                    'entryDate_baru' => optional($purchase->entryDate)->format('d-m-Y H:i'),
                ])
                ->log("Pembelian #{$purchase->id} berhasil diperbarui");

            return redirect()
                ->route('purchases.show', $purchase->id)
                ->with('success', 'Pembelian berhasil diperbarui.');

        } catch (\Illuminate\Database\QueryException $e) {
            // Tangani error database
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['db_error' => 'Gagal memperbarui pembelian: ' . $e->getMessage()]);

        } catch (\Exception $e) {
            // Tangani error umum
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan, silakan coba lagi.']);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        //
    }
}
