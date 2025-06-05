<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class ProductPurchase extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['purchase_id', 'product_id', 'qty', 'buyPrice', 'expDate', 'subtotal'];

    public function purchase() {
        return $this->belongsTo(Purchase::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }

     public function returnedQty()
    {
        return ReturItem::where('product_id', $this->product_id)
            ->whereHas('retur', function ($query) {
                $query->where('purchase_id', $this->purchase_id);
            })
            ->sum('qty');
    }
}
