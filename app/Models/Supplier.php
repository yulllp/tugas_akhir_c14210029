<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Supplier extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'address', 'phone'];

    public function purchases() {
        return $this->hasMany(Purchase::class);
    }

    public function productPurchases() {
        return $this->hasMany(ProductPurchase::class);
    }

    public function returs() {
        return $this->hasMany(Retur::class);
    }

    public function scopeSearch($query, $value)
    {
        $query->where('name', 'ILIKE', "%{$value}%")->orWhere('phone', 'ILIKE', "%{$value}%")->orWhere('id', 'ILIKE', "%{$value}%");
    }
}