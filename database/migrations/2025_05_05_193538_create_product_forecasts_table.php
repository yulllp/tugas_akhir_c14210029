<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->year('prediction_year');
            $table->json('predicted_values'); 
            $table->json('training_data')->nullable();
            $table->float('alpha')->nullable();
            $table->float('beta')->nullable();
            $table->float('gamma')->nullable();
            $table->float('mae')->nullable(); 
            $table->float('mape')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_forecasts');
    }
};
