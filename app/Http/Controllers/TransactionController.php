<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\TempTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pos.index', [
            'title' => 'Trsansaksi',
        ]);
    }

    public function showPOS()
    {
        $products = Product::with('latestPrice')->get();
        $customers = Customer::all();
        return view('pos.create', [
            'title' => 'Point of Sale',
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    public function getTempTransaction()
    {
        $tempTransactions = TempTransaction::with(['product.latestPrice'])
            ->where('user_id', Auth::id())
            ->orderBy('id', 'asc')           // enforce consistent ordering
            ->get();

        // Optional: Format for frontend clarity
        $formatted = $tempTransactions->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'price' => $item->product->latestPrice->sellPrice ?? 0,
                'disc' => $item->discount,
                'qty' => $item->qty,
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
            'disc' => 'nullable|integer|min:0',
        ]);

        $userId = Auth::id(); // or session('cashier_id') if not using auth

        // Prevent duplicates for the same user
        $existing = TempTransaction::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Produk sudah ditambahkan.'
            ]);
        }

        $product = Product::with('latestPrice')->findOrFail($request->product_id);
        $price = $product->latestPrice->sellPrice ?? 0;
        $disc = $request->disc ?? 0;
        $qty = $request->qty ?? 0;

        $subtotal = ($price * $qty) - ($disc * $qty);

        TempTransaction::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'qty' => $qty,
            'discount' => $disc,
            'subtotal' => $subtotal,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
        ]);
    }

    public function deleteTempWithAuth(Request $request)
    {
        if (Auth::check() && Auth::user()->role === 'owner') {
            TempTransaction::findOrFail($request->id)->delete();
            return response()->json(['success' => true]);
        }

        $request->validate([
            'id' => 'required|exists:temp_transactions,id',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.']);
        }

        TempTransaction::findOrFail($request->id)->delete();

        return response()->json(['success' => true]);
    }

    public function authorizeSupervisorCredit(Request $request)
    {
        if (Auth::check() && Auth::user()->role === 'owner') {
            return response()->json(['success' => true]);
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)
            ->where('role', 'owner')
            ->where('status', 'active') // check if typo here; should probably be 'status'
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.']);
        }

        return response()->json(['success' => true]);
    }


    public function show(Transaction $transaction)
    {
        $title = 'Detail Penjualan #' . $transaction->id;
        $transaction->load('detailTransactions.product');
        return view('pos.show', compact('transaction', 'title'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $final = json_decode($request->input('final_transaction'), true);

        if (!$final || !is_array($final)) {
            return redirect()
                ->route('transactions.create')
                ->with('error', 'Format penjualan tidak valid.');
        }

        $validator = Validator::make($final, [
            'total' => 'required|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'credit' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('transactions.create')
                ->withErrors($validator)
                ->withInput();
        }

        $userId = Auth::id();

        try {
            // Retrieve temp items
            $tempItems = TempTransaction::where('user_id', $userId)->get();

            if ($tempItems->isEmpty()) {
                throw new \Exception('Tidak ada item dalam penjualan.');
            }

            // First pass: atomic decrement of stock for all items
            foreach ($tempItems as $item) {
                $updated = Product::where('id', $item->product_id)
                    ->where('totalStok', '>=', $item->qty)
                    ->decrement('totalStok', $item->qty);

                if (!$updated) {
                    throw new \Exception("Stok tidak mencukupi untuk produk ID: {$item->product_id}");
                }
            }

            // All decrements succeeded; now create transaction header
            $transaction = Transaction::create([
                'user_id' => $userId,
                'customer_id' => $final['customer_id'] ?? null,
                'transaction_at' => now(),
                'total' => $final['total'],
                'prePaid' => $final['paid'],
                'status' => $final['credit'] ? 'unpaid' : 'paid',
            ]);

            // Second pass: insert details and notify low stock
            foreach ($tempItems as $item) {
                $productPrice = ProductPrice::where('product_id', $item->product_id)
                    ->latest('created_at')
                    ->first();

                DetailTransaction::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'product_price_id' => $productPrice?->id,
                    'qty' => $item->qty,
                    'discount' => $item->discount,
                    'subtotal' => $item->subtotal,
                ]);

                $product = Product::find($item->product_id);
                if ($product->totalStok < $product->minStok) {
                    $owners = User::where('role', 'owner')->get();
                    Notification::send($owners, new LowStockNotification($product));
                }
            }

            // Clear temp items and log activity
            TempTransaction::where('user_id', $userId)->delete();

            activity('penjualan')
                ->performedOn($transaction)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id' => $transaction->id,
                    'total' => $transaction->total,
                    'dibayar' => $transaction->prePaid,
                    'status' => $transaction->status,
                    'pelanggan_id' => $transaction->customer_id,
                ])
                ->log("Penjualan #{$transaction->id} berhasil dibuat");

            return redirect()
                ->route('transactions.create')
                ->with('success', 'Penjualan berhasil')
                ->with('transaction_id', $transaction->id);
        } catch (\Exception $e) {
            Log::error('Transaction error: ' . $e->getMessage());

            return redirect()
                ->route('transactions.create')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function print(Transaction $transaction)
    {
        $data = [
            'code' => $transaction->id,
            'date' => \Carbon\Carbon::parse($transaction->transaction_at)->format('d-m-Y H:i'),
            'cashier' => $transaction->user->name,
            'total' => number_format($transaction->total),
            'paid' => number_format($transaction->prePaid),
            'status' => $transaction->status,
            'items' => $transaction->detailTransactions->map(fn($dt) => [
                'name' => $dt->product->name,
                'qty' => $dt->qty,
                'price' => number_format($dt->productPrice->sellPrice ?? 0),
                'disc' => number_format($dt->discount),
                'subtotal' => number_format($dt->subtotal),
            ]),
        ];

        return view('print.receipt', compact('data'));
    }

    /**
     * Display the specified resource.
     */

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        $transaction->load(['customer', 'detailTransactions.product', 'detailTransactions.productPrice']);

        // Pass the transaction (with its current customer) to the view
        return view('pos.edit', [
            'transaction' => $transaction,
            'title' => 'Edit Penjualan #' . $transaction->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'selected_customer_id' => 'nullable|exists:customers,id',
        ]);

        $lamaPelanggan = $transaction->customer_id;

        $transaction->customer_id = $validated['selected_customer_id'];
        $transaction->save();

        activity('penjualan')
            ->performedOn($transaction)
            ->causedBy(Auth::user())
            ->withProperties([
                'id' => $transaction->id,
                'lama' => [
                    'pelanggan_id' => $lamaPelanggan,
                ],
                'baru' => [
                    'pelanggan_id' => $validated['selected_customer_id'],
                ],
            ])
            ->log("Penjualan #{$transaction->id} berhasil diperbarui");

        return redirect()->route('transactions.show', $transaction->id)->with('success', 'Trasaksi berhasil diubah');;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
