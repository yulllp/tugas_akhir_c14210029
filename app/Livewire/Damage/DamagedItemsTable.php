<?php

namespace App\Livewire\Damage;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\ReturItem;
use App\Models\DetailStokOpname;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DamagedItemsTable extends Component
{
    use WithPagination;

    // --- query‑string & URL sync
    #[Url(history: true)] public $search        = '';
    #[Url(history: true)] public $sortField     = 'date';
    #[Url(history: true)] public $sortDirection = 'desc';
    #[Url(history: true)] public $filterStatus  = 'all';
    #[Url(history: true)] public $startDate;
    #[Url(history: true)] public $endDate;
    #[Url(history: true)] public $perPage       = 10;

    public $updatingItemId = null; // Track which item is being updated

    protected $queryString = [
        'search',
        'sortField',
        'sortDirection',
        'filterStatus',
        'startDate',
        'endDate',
        'page'
    ];

    // --- UI helpers
    public function setFilter(string $status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function setSortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
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

    // --- change handling status
    public function updateHandling(string $source, int $id, string $newStatus)
    {
        $this->updatingItemId = $id; // Set the updating item ID

        try {
            if ($source === 'retur') {
                $item = ReturItem::findOrFail($id);
            } else {
                $item = DetailStokOpname::findOrFail($id);
            }

            $item->handling = $newStatus;
            $item->save(); // Use save() instead of update() to ensure model events fire

            session()->flash('success', 'Status handling diperbarui.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memperbarui status: ' . $e->getMessage());
        } finally {
            $this->updatingItemId = null; // Reset the updating item ID
        }
    }

    public function render()
    {
        // 1) Build each piece of the UNION
        $returQ = ReturItem::select([
            'retur_items.id',
            DB::raw("'Customer Retur' AS type"),
            'retur_items.retur_id     AS related_id',
            'products.name            AS product',
            'retur_items.qty',
            'returs.return_date       AS date',
            'retur_items.note        AS description',
            'retur_items.handling',
            DB::raw("'retur'           AS source"),
        ])
            ->join('products', 'products.id', 'retur_items.product_id')
            ->join('returs',   'returs.id',  'retur_items.retur_id')
            ->where('retur_items.condition', 'damaged')
            ->when(
                $this->filterStatus !== 'all',
                fn($q) => $q->where('retur_items.handling', $this->filterStatus)
            )
            ->when(
                $this->startDate && $this->endDate,
                fn($q) => $q->whereBetween('returs.return_date', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ])
            );

        $opnameQ = DetailStokOpname::select([
            'detail_stok_opnames.id',
            DB::raw("'Stock Opname' AS type"),
            'detail_stok_opnames.schedule_id AS related_id',
            'products.name                     AS product',
            DB::raw('ABS(detail_stok_opnames.difference) AS qty'),
            'stok_opname_schedules.date        AS date',
            'detail_stok_opnames.description          AS description',
            'detail_stok_opnames.handling',
            DB::raw("'opname'         AS source"),
        ])
            ->join('products', 'products.id', 'detail_stok_opnames.product_id')
            ->join('stok_opname_schedules', 'stok_opname_schedules.id', 'detail_stok_opnames.schedule_id')
            ->where('detail_stok_opnames.difference', '<', 0)
            ->when(
                $this->filterStatus !== 'all',
                fn($q) => $q->where('detail_stok_opnames.handling', $this->filterStatus)
            )
            ->when(
                $this->startDate && $this->endDate,
                fn($q) => $q->whereBetween('stok_opname_schedules.date', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ])
            );

        // 2) Union & wrap so we can paginate
        $union = $returQ->unionAll($opnameQ);
        $items = DB::table(DB::raw("({$union->toSql()}) AS all_items"))
            ->mergeBindings($union->getQuery())
            ->when(
                $this->search,
                fn($q) =>
                $q->where(
                    fn($q2) =>
                    $q2->where('product', 'ILIKE', "%{$this->search}%")
                        ->orWhereRaw("related_id::text ILIKE ?", ["%{$this->search}%"])
                )
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.damage.damaged-items-table', [
            'items' => $items,
        ]);
    }
}
