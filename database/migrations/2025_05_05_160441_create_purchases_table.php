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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->dateTime('buyDate');
            $table->foreignId('supplier_id')->constrained();
            $table->string('faktur');
            $table->decimal('total', 12, 0);
            $table->decimal('prePaid', 12, 0);
            $table->enum('status', ['paid', 'unpaid']);
            $table->string('shipping');
            $table->dateTime('entryDate')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
