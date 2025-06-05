<?php

namespace App\Livewire\Transaction;

use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\TempTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class PosPage extends Component
{
    public $productName;
    public $selectedProductId;

    public array $products = [];

    public $price;
    public $stock;
    public $qty;
    public $disc = 0;

    public $total;
    public $customerName;
    public $selectedCustomerId;
    public $isCredit = false;
    public $prePaid;
    public $deleteItemId;
    protected $listeners = ['confirmDelete'];

    public function updatedProductName($value)
    {
        $this->selectedProductId = null;

        if (strlen($value) >= 2) {
            $this->products = Product::where('name', 'ILIKE', '%' . $value . '%')
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->toArray();
        } else {
            $this->products = [];
        }
    }

    public function selectProduct($id, $name)
    {
        $this->selectedProductId = $id;
        $this->productName = $name;
        $this->products = [];
    }

    public function addItem()
    {
        if (!$this->selectedProduct || $this->qty < 1) return;

        $price = $this->selectedProduct->price;
        $subtotal = ($price * $this->qty) - $this->discount;

        TempTransaction::create([
            'user_id' => Auth::id(),
            'product_id' => $this->selectedProduct->id,
            'product_name' => $this->selectedProduct->name,
            'qty' => $this->qty,
            'price' => $price,
            'discount' => $this->discount,
            'subtotal' => $subtotal,
        ]);

        $this->reset(['productSearch', 'selectedProduct', 'qty', 'discount']);
    }

    public function getItemsProperty()
    {
        return TempTransaction::where('user_id', Auth::id())->get();
    }

    public function getTotalProperty()
    {
        return $this->items->sum('subtotal');
    }

    public function confirmDelete($id)
    {
        $this->deleteItemId = $id;
        $this->dispatchBrowserEvent('open-supervisor-modal');
    }

    public function approveDelete()
    {
        $user = User::where('username', $this->supervisorUsername)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->first();

        if ($user && Hash::check($this->supervisorPassword, $user->password)) {
            TempTransaction::where('id', $this->deleteItemId)->delete();
            $this->reset(['supervisorUsername', 'supervisorPassword', 'deleteItemId']);
            $this->dispatchBrowserEvent('close-supervisor-modal');
            session()->flash('message', 'Item successfully deleted');
        } else {
            session()->flash('error', 'Supervisor credentials invalid');
        }
    }

    // public function storeTransaction()
    // {
    //     DB::transaction(function () {
    //         $transaction = Transaction::create([
    //             'user_id' => Auth::id(),
    //             'customer_name' => $this->customerName,
    //             'total' => $this->total,
    //             'nominal_paid' => $this->nominalPaid,
    //             'is_credit' => $this->isCredit,
    //         ]);

    //         foreach ($this->items as $item) {
    //             DetailTransaction::create([
    //                 'transaction_id' => $transaction->id,
    //                 'product_id' => $item->product_id,
    //                 'qty' => $item->qty,
    //                 'price' => $item->price,
    //                 'discount' => $item->discount,
    //                 'subtotal' => $item->subtotal,
    //             ]);
    //             // Update stock, if needed
    //         }

    //         TempTransaction::where('user_id', Auth::id())->delete();
    //     });

    //     return redirect()->route('pos')->with('transaction_id', $transaction->id);
    // }

    public function render()
    {
        return view('livewire.transaction.pos-page', [
            'products' => $this->products,
        ]);
    }
}
