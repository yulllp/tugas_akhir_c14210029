<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class CreditPurchase extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['purchase_id', 'payDate', 'payment_total', 'description'];

    public function purchase() {
        return $this->belongsTo(Purchase::class);
    }
}
