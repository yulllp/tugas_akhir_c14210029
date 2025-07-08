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
        Schema::table('retur_items', function (Blueprint $table) {
            $table->enum('handling', ['belum_ditangani','buang','daur_ulang'])
                  ->default('belum_ditangani')
                  ->after('condition');
        });

        Schema::table('detail_stok_opnames', function (Blueprint $table) {
            $table->enum('handling', ['belum_ditangani','buang','daur_ulang'])
                  ->default('belum_ditangani')
                  ->after('difference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retur_items', function (Blueprint $table) {
            $table->dropColumn('handling');
        });

        Schema::table('detail_stok_opnames', function (Blueprint $table) {
            $table->dropColumn('handling');
        });
    }
};
