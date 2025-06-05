<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class DetailTransaction extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['transaction_id', 'product_id', 'product_price_id', 'qty', 'discount', 'subtotal'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productPrice()
    {
        return $this->belongsTo(ProductPrice::class);
    }

    public function returnedQty()
    {
        return ReturItem::where('product_id', $this->product_id)
            ->whereHas('retur', function ($query) {
                $query->where('transaction_id', $this->transaction_id);
            })
            ->sum('qty');
    }   
}
