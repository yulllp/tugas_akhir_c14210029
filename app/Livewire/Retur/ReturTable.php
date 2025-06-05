<?php

namespace App\Livewire\Retur;

use App\Models\Retur;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ReturTable extends Component
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
    public $filterType = 'all';

    #[Url(history: true)]
    public $startDate;
    #[Url(history: true)]
    public $endDate;

    protected $queryString = ['filterType'];

    public function setFilter($type)
    {
        $this->filterType = $type;
        $this->resetPage();
    }

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
        $returs = Retur::with(['transaction', 'purchase', 'items.product'])
            ->when($this->filterType === 'customer', fn($q) => $q->where('return_type', 'customer'))
            ->when($this->filterType === 'supplier', fn($q) => $q->where('return_type', 'supplier'))
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
            ->paginate(10);

        return view('livewire.retur.retur-table', [
            'returs' => $returs,
        ]);
    }
}
