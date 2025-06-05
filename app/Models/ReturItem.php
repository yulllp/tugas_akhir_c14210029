<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class ReturItem extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
     protected $fillable = [
        'retur_id',
        'product_id',
        'qty',
        'product_price_id',
        'buy_price',
        'disc',
        'subtotal',
        'condition',
        'note',

    ];

    // Relationships

    public function retur()
    {
        return $this->belongsTo(Retur::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productPrice()
    {
        return $this->belongsTo(ProductPrice::class);
    }

}
