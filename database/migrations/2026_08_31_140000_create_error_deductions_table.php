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
        Schema::create('error_deductions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('period_month', 7); // Format: YYYY-MM
            $table->date('error_date'); // Tanggal terjadinya kesalahan produksi/log error
            $table->string('error_title'); // Judul/jenis error (misal: "Kesalahan Cetak Finishing", "Rusak Bahan")
            $table->text('description')->nullable(); // Keterangan detail / kronologi / nomor SPK
            $table->decimal('total_error_cost', 15, 2)->default(0); // Total nilai kerugian biaya
            $table->decimal('amount', 15, 2)->default(0); // Nominal yang dipotongkan ke karyawan
            $table->enum('deduction_source', ['payroll', 'syirkah_mandatory', 'syirkah_secondary', 'syirkah_all'])->default('payroll');
            $table->enum('status', ['pending', 'approved', 'processed', 'cancelled'])->default('pending');
            $table->boolean('is_applied')->default(false);
            $table->foreignUlid('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->foreignUlid('saving_transaction_id')->nullable()->constrained('saving_transactions')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
            $table->index(['period_month', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_deductions');
    }
};
