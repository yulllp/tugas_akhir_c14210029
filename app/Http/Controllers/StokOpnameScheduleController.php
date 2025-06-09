<?php

namespace App\Http\Controllers;

use App\Models\DetailStokOpname;
use App\Models\Product;
use App\Models\StokOpnameSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StokOpnameScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('opname.index', [
            'title' => 'Stok Opname',
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
    public function store(Request $request)
    {

        // Validasi input
        $request->validate([
            'tanggal_opname' => 'required|date_format:Y-m-d\TH:i',
            'description'    => 'nullable|string',
        ]);

        try {

            // Coba menyimpan ke database
            $schedule = StokOpnameSchedule::create([
                'date'        => $request->tanggal_opname,
                'description' => $request->description,
                'status'      => 'not_checked',
                'user_id'     => Auth::id(),
            ]);

            activity('stok_opname')
                ->performedOn($schedule)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id_schedule'     => $schedule->id,
                    'tanggal_opname'  => Carbon::parse($schedule->date)->format('d-m-Y H:i'),
                    'keterangan'      => $schedule->description,
                    'dibuat_oleh'     => Auth::user()->name,
                ])
                ->log("Jadwal stok opname #{$schedule->id} berhasil dibuat");

            // Jika sukses, redirect seperti biasa
            return redirect()->back()->with('success', 'Jadwal stok opname berhasil ditambahkan.');
        } catch (\Exception $e) {
            // Jika ada error (misalnya format tanggal salah, koneksi DB, dll), tampilkan detailnya
            dd([
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'input'   => $request->all(),
            ]);
        }
    }


    public function storeDetail(Request $request, $scheduleId)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $schedule = StokOpnameSchedule::findOrFail($scheduleId);

        if ($schedule->status === 'checked') {
            return redirect()->back()->with('error', 'Stok opname sudah diperiksa dan tidak dapat diubah.');
        }

        // DB::beginTransaction();

        try {
            $hasChanges = false;

            foreach ($request->items as $productId => $data) {
                $product = Product::findOrFail($productId);

                $systemStock = $product->totalStok;
                $actualStock = intval($data['actual_stock'] ?? 0);
                $difference = $actualStock - $systemStock;

                if ($difference === 0) {
                    continue;
                }

                if ($difference < 0 && $actualStock < 0) {
                    throw new \Exception("Stok fisik untuk produk {$product->name} tidak boleh negatif.");
                }

                if ($difference < 0 && abs($difference) > $systemStock) {
                    throw new \Exception("Stok untuk produk {$product->name} tidak mencukupi untuk pengurangan.");
                }

                $priceBasis = $difference > 0 ? 'buy' : 'sell';

                $buyPrice = $product->productPurchases()->latest()->first()?->buyPrice ?? 0;
                $sellPrice = $product->latestPrice()->first()?->sellPrice ?? 0;
                $priceUsed = $priceBasis === 'buy' ? $buyPrice : $sellPrice;

                DetailStokOpname::create([
                    'schedule_id'   => $schedule->id,
                    'product_id'    => $product->id,
                    'stok_sistem'   => $systemStock,
                    'stok_fisik'    => $actualStock,
                    'price_basis'   => $priceBasis,
                    'price_used'    => $priceUsed,
                    'difference'    => $difference,
                    'description'   => $data['description'] ?? null,
                ]);

                $product->update([
                    'totalStok' => $actualStock,
                ]);

                $hasChanges = true;
            }

            if (!$hasChanges) {
                DB::rollBack();
                return redirect()
                    ->back()
                    ->with('info', 'Tidak ada perubahan stok yang disimpan karena stok fisik sama dengan stok sistem.');
            }

            $schedule->update([
                'status' => 'checked',
                'finish_at'  => now(),
            ]);

            // DB::commit();

            activity('stok_opname')
                ->performedOn($schedule)
                ->causedBy(Auth::user())
                ->withProperties([
                    'id_schedule'     => $schedule->id,
                    'selesai_pada'    => $schedule->finish_at->format('d-m-Y H:i'),
                ])
                ->log("Detail stok opname untuk jadwal #{$schedule->id} berhasil disimpan");

            return redirect()
                ->route('opnames.index')
                ->with('success', 'Detail stok opname berhasil disimpan, stok diperbarui, dan jadwal telah dicek.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal menyimpan stok opname: ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(StokOpnameSchedule $stockOpnameSchedule)
    {
        // Eager load related detail records with their associated products
        $stockOpnameSchedule->load('detailStokOpnames.product');

        // If the schedule has been checked, get the list of detailStokOpnames only
        if ($stockOpnameSchedule->status === 'checked') {
            // Just use the already loaded detailStokOpnames for display
            $details = $stockOpnameSchedule->detailStokOpnames;
        } else {
            // Show full product list (e.g. for input before checking)
            $products = Product::orderBy('name')->get();
        }

        return view('opname.show', [
            'schedule' => $stockOpnameSchedule,
            'products' => $products ?? null,
            'details' => $details ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StokOpnameSchedule $stokOpnameSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StokOpnameSchedule $stokOpnameSchedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StokOpnameSchedule $stokOpnameSchedule)
    {
        //
    }
}
