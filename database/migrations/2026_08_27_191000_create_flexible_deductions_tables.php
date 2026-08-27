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
        // 1. Master Program Potongan Fleksibel / Galang Dana
        Schema::create('flexible_deduction_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('period_month', 7); // Format: YYYY-MM
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'processed', 'archived'])->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_month', 'status']);
        });

        // 2. Data Potongan per Karyawan
        Schema::create('flexible_deductions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('program_id')->constrained('flexible_deduction_programs')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('period_month', 7); // Format: YYYY-MM
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->boolean('is_applied')->default(false);
            $table->foreignUlid('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->timestamps();

            $table->unique(['program_id', 'user_id', 'period_month'], 'idx_flex_ded_prog_user_month');
            $table->index(['user_id', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flexible_deductions');
        Schema::dropIfExists('flexible_deduction_programs');
    }
};
