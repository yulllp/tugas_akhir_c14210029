<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['name', 'address', 'phone'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeSearch($query, $value)
    {
        $query->where('name', 'ILIKE', "%{$value}%")->orWhere('phone', 'ILIKE', "%{$value}%")->orWhere('id', 'ILIKE', "%{$value}%");
    }
}
