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
        Schema::table('barangs', function (Blueprint $table) {
            $table->decimal('harga_pokok', 15, 2)->default(0)->after('harga'); // Harga pokok/cost
            $table->decimal('margin_percentage', 8, 2)->default(0)->after('harga_pokok'); // Margin keuntungan %
            $table->decimal('minimum_stock_value', 15, 2)->default(0)->after('minimum_stock'); // Nilai minimum stock
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropColumn(['harga_pokok', 'margin_percentage', 'minimum_stock_value']);
        });
    }
};
