<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Purchase;
use App\Models\Supplier;

class SupplierCreditTable extends Component
{
    use WithPagination;

    // URL‐driven params so you can share via query string & keep state on back/refresh
    // exactly the same as CustomerCreditTable, just renamed to “supplier_id”
    #[Url(history: true)] public $search        = '';
    #[Url(history: true)] public $sortField     = 'purchase_at';
    #[Url(history: true)] public $sortDirection = 'desc';
    #[Url(history: true)] public $perPage       = 10;

    #[Url(history: true)]
    public $sortBy = 'purchase_at';

    #[Url(history: true)] public $supplier_id   = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSupplierId()
    {
        $this->resetPage();
    }

    public function setSortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }

        $this->sortField     = $field;
        $this->sortDirection = 'asc';
    }

    public function render()
    {
        // 1) Ambil semua supplier untuk dropdown
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        // 2) Buat base query: hanya yang “unpaid”
        $baseQuery = Purchase::with(['supplier', 'returs.items', 'creditPurchase'])
            ->where('status', 'unpaid');

        // 3) Jika ada filter supplier_id, tambahkan WHERE
        if ($this->supplier_id) {
            $baseQuery->where('supplier_id', $this->supplier_id);
        }

        // 4) Tambahkan search (bisa cari ID purchase atau nama supplier)
        if ($this->search) {
            $term = '%' . $this->search . '%';
            $baseQuery->where(function($q) use ($term) {
                $q->where('id', 'like', $term)
                  ->orWhereHas('supplier', function($q2) use ($term) {
                      $q2->where('name', 'like', $term);
                  });
            });
        }

        // 5) Hitung summary totals _tanpa_ pagination
        $allForSummary = (clone $baseQuery)->get();

        $totalTagihan   = 0;
        $totalPaid      = 0;
        $totalRefund    = 0;
        $totalRemaining = 0;

        foreach ($allForSummary as $purchase) {
            // a) Hitung totalReturNominal dari setiap ReturItem
            $totalReturNominal = $purchase
                ->returs
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            // b) Net total setelah retur
            $netTotal = $purchase->total - $totalReturNominal;
            if ($netTotal < 0) {
                $netTotal = 0;
            }

            // c) Tambahkan ke totalRefund
            $totalRefund += $totalReturNominal;

            // d) Hitung sudah dibayar = prePaid + creditPurchase
            $prePaid         = $purchase->prePaid;
            $creditPaidSoFar = $purchase->creditPurchase->sum('payment_total');
            $alreadyPaid     = $prePaid + $creditPaidSoFar;
            if ($alreadyPaid < 0) {
                $alreadyPaid = 0;
            }
            $totalPaid += $alreadyPaid;

            // e) Hitung sisa utang
            $remaining = $netTotal - $alreadyPaid;
            if ($remaining < 0) {
                $remaining = 0;
            }
            $totalRemaining += $remaining;

            // f) Tambahkan ke totalTagihan
            $totalTagihan += $netTotal;
        }

        // 6) Pagination untuk tabel
        $purchases = (clone $baseQuery)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.credit.supplier-credit-table', [
            'suppliers'     => $suppliers,
            'purchases'     => $purchases,
            'summaryTotals' => [
                'totalTagihan'   => $totalTagihan,
                'totalPaid'      => $totalPaid,
                'totalRefund'    => $totalRefund,
                'totalRemaining' => $totalRemaining,
            ],
        ]);
    }
}
