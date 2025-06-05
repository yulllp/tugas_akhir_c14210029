<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseTable extends Component
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
    public $shipFilter = 'semua';
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

    public function updatedShipFilter()
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
        $query = Purchase::search($this->search);

        if ($this->statusFilter === 'paid') {
            $query->where('status', 'paid');
        } elseif ($this->statusFilter === 'unpaid') {
            $query->where('status', 'unpaid');
        }

        if ($this->shipFilter === 'arrive') {
            $query->where('shipping', 'arrive');
        } elseif ($this->shipFilter === 'pending') {
            $query->where('shipping', 'pending');
        }

        if ($this->startDate && $this->endDate) {
            try {
                $start = Carbon::parse($this->startDate)->startOfDay();
                $end = Carbon::parse($this->endDate)->endOfDay();
                $query->whereBetween('buyDate', [$start, $end]);
            } catch (\Exception $e) {
                logger()->error('Date parse error: ' . $e->getMessage());
            }
        }

        $purchases = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.purchase.purchase-table', [
            'purchases' => $purchases,
        ]);
    }
}
