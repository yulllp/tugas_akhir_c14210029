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
        Schema::create('retur_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->constrained('returs')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->integer('qty');
            $table->foreignId('product_price_id')->nullable()->constrained('product_prices');   
            $table->decimal('buy_price', 16, 0)->nullable();
            $table->integer('disc')->default(0);
            $table->integer('subtotal');
            $table->enum('condition', ['good', 'damaged'])->default('good');
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_items');
    }
};
