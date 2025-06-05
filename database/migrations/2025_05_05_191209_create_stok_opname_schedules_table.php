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
        Schema::create('stok_opname_schedules', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->text('description')->nullable();
            $table->string('status');
            $table->foreignId('user_id')->constrained();
            $table->dateTime('finish_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_opname_schedules');
    }
};
