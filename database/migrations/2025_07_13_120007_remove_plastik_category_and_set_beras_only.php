<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update semua produk plastik menjadi beras
        DB::table('produk')->where('kategori', 'plastik')->update([
            'kategori' => 'beras'
        ]);

        // Hapus semua produk yang bukan kategori beras (jika ada)
        DB::table('produk')->where('kategori', '!=', 'beras')->delete();

        // Update constraint kategori di database jika ada
        // Ini akan memastikan hanya kategori 'beras' yang diizinkan
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak ada rollback karena ini adalah perubahan permanen
        // untuk menyederhanakan sistem hanya untuk toko beras
    }
};
