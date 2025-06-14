<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class ProductPrice extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['product_id', 'sellPrice'];

    public function product() {
        return $this->belongsTo(Product::class);
    }

}
