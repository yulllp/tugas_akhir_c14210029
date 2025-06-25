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
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\EscposImage;

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
        $title = 'Detail Transaksi #' . $transaction->id;
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
                ->with('error', 'Format transaksi tidak valid.');
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
                throw new \Exception('Tidak ada item dalam transaksi.');
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

            activity('transaksi')
                ->performedOn($transaction)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id' => $transaction->id,
                    'total' => $transaction->total,
                    'dibayar' => $transaction->prePaid,
                    'status' => $transaction->status,
                    'pelanggan_id' => $transaction->customer_id,
                ])
                ->log("Transaksi #{$transaction->id} berhasil dibuat");

            return redirect()
                ->route('transactions.create')
                ->with('success', 'Transaksi berhasil')
                ->with('transaction_id', $transaction->id);
        } catch (\Exception $e) {
            Log::error('Transaction error: ' . $e->getMessage());

            return redirect()
                ->route('transactions.create')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function print(Request $request, Transaction $transaction)
    {
        // (Optional) Authorization: ensure user can print this
        // $this->authorize('view', $transaction);

        // Format your data
        $data = [
            'code'    => $transaction->id,
            'date'    => Carbon::parse($transaction->transaction_at)
                ->format('d-m-Y H:i'),
            'cashier' => $transaction->user->name,
            'status'  => strtoupper($transaction->status),
            'total'   => $transaction->total,
            'paid'    => $transaction->prePaid,
            'items'   => $transaction->detailTransactions,
        ];


        try {
            $connector = new WindowsPrintConnector("\\\\localhost\\POS-58");

            // 2) (Optional) load capability profile if using graphics:
            $profile = CapabilityProfile::load("default");

            // 3) Printer instance
            $printer = new Printer($connector, $profile);

            // 4) Print header
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
            $printer->text("TOKO YAMDENA PLAZA\n");
            $printer->selectPrintMode();
            $printer->text("Jl. BHINEKA No. 5-6, SAUMLAKI\n");
            $printer->feed();

            // 5) Transaction meta
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Kode   : {$data['code']}\n");
            $printer->text("Tanggal: {$data['date']}\n");
            $printer->text("Kasir  : {$data['cashier']}\n");
            $printer->text("Status : {$data['status']}\n");
            $printer->feed();

            // 6) Items
            foreach ($data['items'] as $item) {
                $name = $item->product->name;
                $qty  = $item->qty;
                $price = $item->productPrice->sellPrice ?? 0;
                $disc  = $item->discount;

                $printer->text($name . "\n");
                $printer->text(sprintf(
                    "  %dx Rp%s = Rp%s\n",
                    $qty,
                    number_format($price),
                    number_format($qty * $price)
                ));
                if ($disc > 0) {
                    $printer->text(sprintf(
                        "  Disc %dx Rp%s = -Rp%s\n",
                        $qty,
                        number_format($disc),
                        number_format($qty * $disc)
                    ));
                }
            }
            $printer->feed();

            // 7) Totals
            $printer->setEmphasis(true);
            $printer->text(sprintf("TOTAL Rp%s\n", number_format($data['total'])));
            $printer->text(sprintf("BAYAR Rp%s\n",  number_format($data['paid'])));
            $printer->setEmphasis(false);
            $printer->feed(2);

            // 8) Cut & drawer
            $printer->cut();
            $printer->pulse();   // open cash drawer

            // 9) Close connection
            $printer->close();

            return response()->json(['message' => 'Printed successfully.']);
        } catch (\Exception $e) {
            Log::error("ESC/POS print error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to print: ' . $e->getMessage()
            ], 500);
        }
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
            'title' => 'Edit Transaksi #' . $transaction->id,
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

        activity('transaksi')
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
            ->log("Transaksi #{$transaction->id} berhasil diperbarui");

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
