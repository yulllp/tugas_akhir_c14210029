<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
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

    public function setSortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::search($this->search)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.user.user-table', [
            'users' => $users,
        ]);
    }
}
