<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Retur extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'return_type',
        'return_date',
        'description',
        'transaction_id',
        'purchase_id',
        'refund_amount',
        'user_id'
    ];

    protected $casts = [
        'return_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ReturItem::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function scopeSearch($query, $term)
    {
        $term = trim($term);

        // If the term is numeric, try matching with transaction_id or purchase_id
        if (is_numeric($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('transaction_id', $term)
                    ->orWhere('purchase_id', $term);
            });
        }

        return $query;
    }

    
}
