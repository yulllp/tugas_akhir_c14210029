<?php

namespace App\Livewire\Customer\Credit;

use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CreditTable extends Component
{
    use WithPagination;
    #[Url(history: true)]
    public $id;
    #[Url(history: true)]
    public $sortField = 'created_at';
    #[Url(history: true)]
    public $sortDirection = 'desc';
    #[Url(history: true)]
    public $sortBy = 'created_at';
    #[Url(history: true)]
    public $perPage = 10;

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
        $payments = CreditPayment::query()
            ->where('transaction_id', $this->id)                // filter by that transaction
            ->when($this->startDate, fn($q) =>
            $q->whereDate('payDate', '>=', $this->startDate))
            ->when($this->endDate, fn($q) =>
            $q->whereDate('payDate', '<=', $this->endDate))
            ->orderBy($this->sortBy, $this->sortDirection)      // e.g. payDate / payment_total                    // scope on CreditPayment
            ->paginate($this->perPage);
        return view('livewire.customer.credit.credit-table', [
            'payments' => $payments,
        ]);
    }
}
