<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index()
    {
        // Fetch all suppliers
        $suppliers = Supplier::all();
        return view('supplier.index', compact('suppliers'));
    }

    public function getSupplier(Request $request)
    {
        $term = $request->get('term');
        $results = Supplier::where('name', 'ILIKE', '%' . $term . '%')->get(['id', 'name']);
        return response()->json($results);
    }

    public function create()
    {
        return view('supplier.create', [
            'title' => 'Tambah Supplier'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:suppliers,name',
            'phone' => 'nullable|unique:suppliers,phone',
            'address' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        activity('supplier')
            ->performedOn($supplier)
            ->causedBy(Auth::user())
            ->withProperties([
                'id'      => $supplier->id,
                'nama'    => $supplier->name,
                'telepon' => $supplier->phone,
                'alamat'  => $supplier->address,
            ])
            ->log("Supplier #{$supplier->id} berhasil ditambahkan");

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('supplier.edit', [
            'supplier' => $supplier,
            'title' => 'Edit Supplier'
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {

        $validated = $request->validate([
            'name' => 'required|unique:suppliers,name,' . $supplier->id,
            'phone' => 'nullable|unique:suppliers,phone,' . $supplier->id,
            'address' => 'nullable|string',
        ]);

        $lama = [
            'nama'    => $supplier->getOriginal('name'),
            'telepon' => $supplier->getOriginal('phone'),
            'alamat'  => $supplier->getOriginal('address'),
        ];

        activity('supplier')
            ->performedOn($supplier)
            ->causedBy(Auth::user())
            ->withProperties([
                'id'   => $supplier->id,
                'lama' => $lama,
                'baru' => [
                    'nama'    => $validated['name'],
                    'telepon' => $validated['phone'],
                    'alamat'  => $validated['address'],
                ],
            ])
            ->log("Supplier #{$supplier->id} berhasil diperbarui");

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }
}
