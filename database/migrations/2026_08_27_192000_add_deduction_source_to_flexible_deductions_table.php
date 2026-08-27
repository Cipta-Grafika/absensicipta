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
        Schema::table('flexible_deductions', function (Blueprint $table) {
            $table->enum('deduction_source', ['payroll', 'syirkah_mandatory', 'syirkah_secondary', 'syirkah_all'])
                  ->default('payroll')
                  ->after('amount');
            $table->foreignUlid('saving_transaction_id')
                  ->nullable()
                  ->after('payroll_id')
                  ->constrained('saving_transactions')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flexible_deductions', function (Blueprint $table) {
            $table->dropForeign(['saving_transaction_id']);
            $table->dropColumn(['deduction_source', 'saving_transaction_id']);
        });
    }
};
