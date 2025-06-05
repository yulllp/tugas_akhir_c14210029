<?php

namespace App\Livewire\Product;

use App\Models\ProductPurchase;
use Livewire\Component;
use Livewire\WithPagination;

class ProductTableBuy extends Component
{
    use WithPagination;

    // URL‐queryable sorting and pagination
    public $sortField     = 'entryDate';
    public $sortDirection = 'desc';
    public $perPage       = 5;

    // the current product ID
    public $id;

    public function mount($id)
    {
        $this->id = $id;
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
        $query = ProductPurchase::query()
            ->select([
                'product_purchases.id',
                'product_purchases.buyPrice',
                'purchases.entryDate',
            ])
            ->join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
            ->where('product_purchases.product_id', $this->id);

        if ($this->sortField === 'entryDate') {
            $query->orderBy('purchases.entryDate', $this->sortDirection);
        } else {
            $query->orderBy("product_purchases.{$this->sortField}", $this->sortDirection);
        }

        $buyRows = $query->paginate($this->perPage);

        return view('livewire.product.product-table-buy', [
            'buyRows' => $buyRows,
        ]);
    }
}
