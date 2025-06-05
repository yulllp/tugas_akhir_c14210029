<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Purchase extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['user_id', 'buyDate', 'supplier_id', 'faktur', 'total', 'status', 'prePaid', 'shipping', 'entryDate', 'expDate'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productPurchase()
    {
        return $this->hasMany(ProductPurchase::class);
    }

    public function creditPurchase()
    {
        return $this->hasMany(CreditPurchase::class);
    }

    public function latestBuyPrice()
    {
        return $this->hasOne(ProductPurchase::class)->latest('created_at');
    }

    public function returs()
    {
        return $this->hasMany(Retur::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch($query, $value)
    {
        $query->where('id', 'ILIKE', "%{$value}%")
            ->orWhereHas('supplier', function ($q) use ($value) {
                $q->where('name', 'ILIKE', "%{$value}%");
            });
    }
}
