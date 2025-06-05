<?php

namespace App\Livewire\Product;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;

class ProductTable extends Component
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

    public function render()
    {
        $products = Product::search($this->search)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.product.product-table', [
            'products' => $products,
        ]);
    }
}
