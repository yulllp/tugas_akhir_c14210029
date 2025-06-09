<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product.index', [
            'title' => 'Stok Barang'
        ]);
    }

    public function getLatestProducts()
    {
        $products = Product::with('latestPrice')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->latestPrice->sellPrice ?? 0,
                'stock' => $product->totalStok ?? 0,
            ];
        });

        return response()->json($products);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product.create', [
            'title' => 'Tambah Produk'
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|unique:products,productCode',
            'name' => 'required|unique:products,name',
            'minStok' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::create([
                'productCode' => $validated['kode'],
                'name' => $validated['name'],
                'minStok' => $validated['minStok'],
                'notify' => false,
            ]);

            ProductPrice::create([
                'product_id' => $product->id,
                'sellPrice' => $validated['price'],
            ]);

            activity('product')
                ->performedOn($product)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id'        => $product->id,
                    'nama'      => $product->name,
                    'kode' => $product->productCode,
                    'harga'     => $validated['price'],
                ])
                ->log("Produk #{$product->id} berhasil ditambahkan");
        });

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with('productPrices')->findOrFail($id);
        return view('product.show', [
            'title' => 'Detail Produk',
            'product' => $product
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::with('productPrices')->findOrFail($id);
        return view('product.edit', [
            'product' => $product,
            'isEditable' => false,
            'title' => 'Edit Produk'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->ignore($product->id),
            ],
            'kode' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'productCode')->ignore($product->id),
            ],
            'minStok' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        // DB::beginTransaction();

        // Update product details
        $product->update([
            'name' => $validated['name'],
            'productCode' => $validated['kode'],
            'minStok' => $validated['minStok'],
        ]);

        // Check and update price if needed
        $latestPrice = $product->productPrices()->orderBy('created_at', 'desc')->first();
        if (!$latestPrice || $latestPrice->sellPrice != $validated['price']) {
            $product->productPrices()->create([
                'sellPrice' => $validated['price'],
                'effectiveDate' => now(),
            ]);
        }

        activity('produk')
            ->performedOn($product)
            ->causedBy(Auth::user())
            ->withProperties([
                'id'  => $product->id,
                'lama' => [
                    'nama'     => $product->getOriginal('name'),
                    'kode'     => $product->getOriginal('productCode'),
                    'stok_min' => $product->getOriginal('minStok'),
                    'harga'    => $latestPrice ? $latestPrice->sellPrice : null,
                ],
                'baru' => [
                    'nama'     => $validated['name'],
                    'kode'     => $validated['kode'],
                    'stok_min' => $validated['minStok'],
                    'harga'    => $validated['price'],
                ],
            ])
            ->log("Produk #{$product->id} berhasil diperbarui");

        // DB::commit();

        // Redirect back to the product show page with success message
        return redirect()->route('products.show', $product->id)->with('success', 'Produk berhasil diperbarui.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
