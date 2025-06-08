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
        Schema::create('returs', function (Blueprint $table) {
            $table->id();
            $table->enum('return_type', ['customer', 'supplier']);
            $table->dateTime('return_date');
            $table->text('description')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained();
            $table->foreignId('purchase_id')->nullable()->constrained();
            $table->decimal('refund_amount', 16, 0)->default(0);
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returs');
    }
};
