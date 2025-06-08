<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Transaction;
use App\Models\Customer;

class CustomerCreditTable extends Component
{
    use WithPagination;

    // URL‐driven params (bisa di‐share via query string)
    #[Url(history: true)] public $search         = '';
    #[Url(history: true)] public $sortField      = 'transaction_at';
    #[Url(history: true)] public $sortDirection  = 'desc';
    #[Url(history: true)] public $perPage        = 10;

    #[Url(history: true)]
    public $sortBy = 'transaction_at';

    // Jadikan customer_id juga URL‐driven, langsung menerapkan filter ketika berubah
    #[Url(history: true)] public $customer_id    = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Ketika user memilih dropdown, kita langsung reset page, dan query akan diterapkan ulang (render akan dipanggil)
    public function updatedCustomerId()
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
        // 1) Ambil semua customer untuk dropdown
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        // 2) Buat query dasar: transaksi 'unpaid'
        $baseQuery = Transaction::with(['customer', 'returs.items', 'creditPayment'])
            ->where('status', 'unpaid');

        // 3) Jika customer_id tidak null, tambahkan where langsung
        if ($this->customer_id) {
            $baseQuery->where('customer_id', $this->customer_id);
        }

        // 4) Tambahkan search (jika ada)
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $baseQuery->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', $searchTerm)
                  ->orWhereHas('customer', function ($q2) use ($searchTerm) {
                      $q2->where('name', 'like', $searchTerm);
                  });
            });
        }

        // 5) Hitung summary tanpa pagination
        $allForSummary = (clone $baseQuery)->get();

        $totalTagihan   = 0;
        $totalPaid      = 0;
        $totalRefund    = 0;
        $totalRemaining = 0;

        foreach ($allForSummary as $trx) {
            // Hitung totalRetur dari subtotal ReturItem
            $totalReturNominal = $trx
                ->returs
                ->flatMap(fn($r) => $r->items)
                ->sum('subtotal');

            // Net total setelah retur
            $netTotal = $trx->total - $totalReturNominal;
            if ($netTotal < 0) {
                $netTotal = 0;
            }

            // Tambahkan ke totalRefund
            $totalRefund += $totalReturNominal;

            // Hitung sudah dibayar = prePaid + creditPayment
            $prePaid         = $trx->prePaid;
            $creditPaidSoFar = $trx->creditPayment->sum('payment_total');
            $alreadyPaid     = $prePaid + $creditPaidSoFar;
            if ($alreadyPaid < 0) {
                $alreadyPaid = 0;
            }
            $totalPaid += $alreadyPaid;

            // Hitung sisa utang
            $remaining = $netTotal - $alreadyPaid;
            if ($remaining < 0) {
                $remaining = 0;
            }
            $totalRemaining += $remaining;

            // Tambahkan ke totalTagihan
            $totalTagihan += $netTotal;
        }

        // 6) Pagination untuk tabel
        $transactions = (clone $baseQuery)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.credit.customer-credit-table', [
            'customers'     => $customers,
            'transactions'  => $transactions,
            'summaryTotals' => [
                'totalTagihan'   => $totalTagihan,
                'totalPaid'      => $totalPaid,
                'totalRefund'    => $totalRefund,
                'totalRemaining' => $totalRemaining,
            ],
        ]);
    }
}
