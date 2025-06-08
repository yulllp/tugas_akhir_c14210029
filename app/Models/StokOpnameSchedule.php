<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class StokOpnameSchedule extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['date', 'description', 'user_id', 'status', 'finish_at'];

    protected $casts = [
        'date'      => 'datetime',
        'finish_at' => 'datetime',
    ];
    public function detailStokOpnames()
    {
        return $this->hasMany(DetailStokOpname::class, 'schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch($query, $value)
    {
        $query->where('id', 'ILIKE', "%{$value}%")
            ->orWhereHas('user', function ($q) use ($value) {
                $q->where('name', 'ILIKE', "%{$value}%");
            });
    }
}
