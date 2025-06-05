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
        Schema::create('detail_stok_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('stok_opname_schedules');
            $table->foreignId('product_id')->constrained();
            $table->integer('stok_sistem');
            $table->integer('stok_fisik');
            $table->enum('price_basis', ['buy', 'sell']);
            $table->decimal('price_used', 16, 0);
            $table->integer('difference');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_stok_opnames');
    }
};
