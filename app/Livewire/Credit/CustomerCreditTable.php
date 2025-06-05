<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerCreditTable extends Component
{
    use WithPagination;

    // URL‐driven params (so you can share links with ?search=…&sortField=…)
    #[Url(history: true)] public $search = '';
    #[Url(history: true)] public $sortField = 'transaction_at';
    #[Url(history: true)] public $sortDirection = 'desc';
    #[Url(history: true)] public $perPage = 10;

    // Filter: selected customer_id (null→all)
    public $customer_id = null;

    // Whenever the search or customer_id changes, reset to page 1
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function updatedCustomerId()
    {
        $this->resetPage();
    }

    // Toggle sorting on a column
    public function setSortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }
        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    // Computed property: “Total outstanding credit” for the selected customer
    public function getTotalCreditProperty()
    {
        if (!$this->customer_id) {
            return 0;
        }

        return Transaction::where('customer_id', $this->customer_id)
            ->where('status', 'unpaid')
            ->select(DB::raw('SUM(total - prePaid) as outstanding_sum'))
            ->value('outstanding_sum') ?? 0;
    }

    public function render()
    {
        // Fetch all customers (to build the dropdown)
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        // Base query: only unpaid transactions
        $query = Transaction::with('customer')
            ->where('status', 'unpaid');

        // If a customer is selected, filter by that ID
        if ($this->customer_id) {
            $query->where('customer_id', $this->customer_id);
        }

        // Basic “search” by transaction ID or by customer name
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'like', $searchTerm)
                  ->orWhereHas('customer', function($q2) use ($searchTerm) {
                      $q2->where('name', 'like', $searchTerm);
                  });
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginate
        $transactions = $query->paginate($this->perPage);

        return view('livewire.credit.customer-credit-table', [
            'customers'    => $customers,
            'transactions' => $transactions,
        ]);
    }
}
