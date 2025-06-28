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
        Schema::table('penjualans', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('metode_pembayaran');
            $table->timestamp('payment_confirmed_at')->nullable()->after('payment_proof');
            $table->foreignId('payment_confirmed_by')->nullable()->after('payment_confirmed_at')->constrained('users')->onDelete('set null');
            $table->timestamp('payment_rejected_at')->nullable()->after('payment_confirmed_by');
            $table->foreignId('payment_rejected_by')->nullable()->after('payment_rejected_at')->constrained('users')->onDelete('set null');
            $table->text('payment_rejection_reason')->nullable()->after('payment_rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropForeign(['payment_confirmed_by']);
            $table->dropForeign(['payment_rejected_by']);
            $table->dropColumn([
                'payment_proof',
                'payment_confirmed_at',
                'payment_confirmed_by',
                'payment_rejected_at',
                'payment_rejected_by',
                'payment_rejection_reason'
            ]);
        });
    }
};
