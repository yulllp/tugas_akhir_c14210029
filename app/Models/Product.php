<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Product extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['productCode', 'name', 'minStok', 'totalStok'];

    public function detailTransactions()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function latestPrice()
    {
        return $this->hasOne(ProductPrice::class)->latest('created_at');
    }

    public function returItems()
    {
        return $this->hasMany(ReturItem::class);
    }

    public function productPurchases()
    {
        return $this->hasMany(ProductPurchase::class);
    }

    public function detailStokOpnames()
    {
        return $this->hasMany(DetailStokOpname::class);
    }

    public function productForcasts()
    {
        return $this->hasOne(ProductForecast::class);
    }

    public function scopeSearch($query, $value)
    {
        $query->where('name', 'ILIKE', "%{$value}%")->orWhere('productCode', 'ILIKE', "%{$value}%");
    }
}
