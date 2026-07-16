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
        Schema::create('loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('loan_amount', 15, 2);
            $table->integer('tenor_months');
            $table->decimal('installment_amount', 15, 2);
            $table->decimal('remaining_balance', 15, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'paid_off'])->default('pending');
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
