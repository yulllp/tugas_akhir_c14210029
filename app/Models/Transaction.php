<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Transaction extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['user_id', 'customer_id', 'transaction_at', 'total', 'prePaid', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function detailTransactions()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    public function creditPayment()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function returs()
    {
        return $this->hasMany(Retur::class, 'transaction_id');
    }

    public function scopeSearch($query, $value)
    {
        $query->where('id', 'ILIKE', "%{$value}%")
            ->orWhereHas('customer', function ($q) use ($value) {
                $q->where('name', 'ILIKE', "%{$value}%");
            });
    }
}
