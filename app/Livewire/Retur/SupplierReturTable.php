<?php

namespace App\Livewire\Retur;

use App\Models\Retur;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierReturTable extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';
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

    public function updatedSearch()
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
        $returs = Retur::with(['purchase', 'items.product'])
            ->where('return_type', 'supplier')
            ->when($this->startDate && $this->endDate, function ($query) {
                try {
                    $start = Carbon::parse($this->startDate)->startOfDay();
                    $end = Carbon::parse($this->endDate)->endOfDay();
                    $query->whereBetween('return_date', [$start, $end]);
                } catch (\Exception $e) {
                    logger()->error('Date parse error: ' . $e->getMessage());
                }
            })
            ->search($this->search)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.retur.supplier-retur-table', [
            'returs' => $returs,
        ]);
    }
}