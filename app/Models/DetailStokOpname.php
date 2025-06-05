<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class DetailStokOpname extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['schedule_id', 'product_id', 'stok_fisik', 'difference', 'description', 'stok_sistem', 'price_used', 'price_basis'];

    public function schedule() {
        return $this->belongsTo(StokOpnameSchedule::class, 'schedule_id');
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
