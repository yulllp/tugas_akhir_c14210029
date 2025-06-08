<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index()
    {
        return view('customer.index', [
            'title' => 'Daftar Pelanggan'
        ]);
    }

    public function getCustomer(Request $request)
    {
        $term = $request->get('term');
        $results = Customer::where('name', 'ILIKE', '%' . $term . '%')->get(['id', 'name']);
        return response()->json($results);
    }

    public function create()
    {
        return view('customer.create', [
            'title' => 'Tambah Pelanggan'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:customers,name',
            'phone' => 'nullable|unique:customers,phone',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        activity('pelanggan')
            ->performedOn($customer)
            ->causedBy(Auth::user())
            ->withProperties([
                'id'      => $customer->id,
                'nama'    => $customer->name,
                'telepon' => $customer->phone,
                'alamat'  => $customer->address,
            ])
            ->log("Pelanggan #{$customer->id} berhasil ditambahkan");

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customer.edit', [
            'customer' => $customer,
            'title' => 'Edit Pelanggan'
        ]);
    }

    public function update(Request $request, Customer $customer)
    {

        $validated = $request->validate([
            'name' => 'required|unique:customers,name,' . $customer->id,
            'phone' => 'nullable|unique:customers,phone,' . $customer->id,
            'address' => 'nullable|string',
        ]);

        $lama = [
            'nama'    => $customer->getOriginal('name'),
            'telepon' => $customer->getOriginal('phone'),
            'alamat'  => $customer->getOriginal('address'),
        ];

        $customer->update($validated);

        activity('pelanggan')
            ->performedOn($customer)
            ->causedBy(Auth::user())
            ->withProperties([
                'id'   => $customer->id,
                'lama' => $lama,
                'baru' => [
                    'nama'    => $validated['name'],
                    'telepon' => $validated['phone'],
                    'alamat'  => $validated['address'],
                ],
            ])
            ->log("Pelanggan #{$customer->id} berhasil diperbarui");

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diperbarui.');
    }
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil dihapus.');
    }
}
