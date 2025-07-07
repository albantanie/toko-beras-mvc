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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_code', 30)->unique(); // e.g., 'PAY-202507-001'
            $table->foreignId('user_id')->constrained('users');
            $table->string('period_month', 7); // e.g., '2025-07'
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->decimal('allowance_amount', 12, 2)->default(0); // Tunjangan
            $table->decimal('deduction_amount', 12, 2)->default(0); // Potongan
            $table->decimal('gross_salary', 12, 2); // Gaji kotor
            $table->decimal('tax_amount', 12, 2)->default(0); // PPh 21
            $table->decimal('insurance_amount', 12, 2)->default(0); // BPJS
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2); // Gaji bersih
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->date('payment_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('breakdown')->nullable(); // Detail breakdown gaji
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
            $table->index(['status', 'period_month']);
            $table->index('payroll_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
