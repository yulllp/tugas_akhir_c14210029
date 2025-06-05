<?php

namespace App\Livewire\Product;

use App\Models\ProductPrice;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductTablePrice extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $sortField = 'created_at';
    #[Url(history: true)]
    public $sortDirection = 'desc';
    public $sortBy = 'created_at';
    #[Url(history: true)]
    public $perPage = 5;

    public $id;

    public function mount($id)
    {
        $this->id = $id;
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

    public function render()
    {
        $productPrices = ProductPrice::where('product_id', $this->id)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.product.product-table-price', [
            'productPrices' => $productPrices,
        ]);
    }
}
