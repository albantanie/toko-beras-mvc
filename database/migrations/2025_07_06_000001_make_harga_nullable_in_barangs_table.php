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
            $table->decimal('harga_beli', 15, 2)->nullable()->change();
            $table->decimal('harga_jual', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->decimal('harga_beli', 15, 2)->nullable(false)->change();
            $table->decimal('harga_jual', 15, 2)->nullable(false)->change();
        });
    }
};
