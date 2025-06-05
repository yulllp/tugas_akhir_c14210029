<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class ProductForecast extends Model
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'product_id',
        'prediction_year',
        'predicted_values',
        'training_data',
        'alpha',
        'beta',
        'gamma',
        'mape',
        'generated_at',
    ];

    protected $casts = [
        'predicted_values' => 'array',
        'training_data' => 'array',
        'generated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
