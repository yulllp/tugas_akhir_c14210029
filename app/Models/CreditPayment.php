<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class CreditPayment extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['transaction_id', 'payDate', 'payment_total', 'description'];

    public function transaction() {
        return $this->belongsTo(Transaction::class);
    }
}
