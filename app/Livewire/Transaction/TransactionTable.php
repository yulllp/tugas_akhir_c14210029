<?php

namespace App\Livewire\Transaction;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionTable extends Component
{
    use WithPagination;
    #[Url(history: true)]
    public $search = '';
    #[Url(history: true)]
    public $sortField = 'created_at';
    #[Url(history: true)]
    public $sortDirection = 'desc';
    #[Url(history: true)]
    public $sortBy = 'created_at';
    #[Url(history: true)]
    public $perPage = 10;

    #[Url(history: true)]
    public $statusFilter = 'semua';
    #[Url(history: true)]
    public $startDate;
    #[Url(history: true)]
    public $endDate;

    public function setSortBy($sortParam)
    {
        if ($this->sortField === $sortParam) {
            $this->sortDirection = ($this->sortDirection == 'asc') ? 'desc' : 'asc';
            return;
        }

        $this->sortField = $sortParam;
        $this->sortDirection = 'asc';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Transaction::search($this->search);

        if ($this->statusFilter === 'paid') {
            $query->where('status', 'paid');
        } elseif ($this->statusFilter === 'unpaid') {
            $query->where('status', 'unpaid');
        }

        if ($this->startDate && $this->endDate) {
            try {
                $start = Carbon::parse($this->startDate)->startOfDay();
                $end = Carbon::parse($this->endDate)->endOfDay();
                $query->whereBetween('transaction_at', [$start, $end]);
            } catch (\Exception $e) {
                logger()->error('Date parse error: ' . $e->getMessage());
            }
        }

        $transactions = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.transaction.transaction-table', [
            'transactions' => $transactions,
        ]);
    }
}
