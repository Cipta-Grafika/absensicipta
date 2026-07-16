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
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->decimal('amount_paid', 15, 2);
            $table->enum('payment_method', ['payroll_deduction', 'savings_deduction', 'cash']);
            $table->foreignUlid('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->foreignUlid('saving_transaction_id')->nullable()->constrained('saving_transactions')->nullOnDelete();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
