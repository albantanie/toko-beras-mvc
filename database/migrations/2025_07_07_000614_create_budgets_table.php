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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 30)->unique(); // e.g., 'BDG-2025-07'
            $table->string('budget_name');
            $table->enum('budget_type', ['monthly', 'quarterly', 'yearly']);
            $table->string('period'); // e.g., '2025-07', '2025-Q3', '2025'
            $table->date('period_start');
            $table->date('period_end');
            $table->string('category'); // e.g., 'operational', 'marketing', 'inventory'
            $table->decimal('planned_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0); // Selisih
            $table->decimal('variance_percentage', 8, 2)->default(0); // Persentase selisih
            $table->enum('status', ['draft', 'approved', 'active', 'completed', 'cancelled'])->default('draft');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->json('breakdown')->nullable(); // Detail breakdown budget
            $table->timestamps();

            $table->index(['budget_type', 'period']);
            $table->index(['status', 'period']);
            $table->index('budget_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
