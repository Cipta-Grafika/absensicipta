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
        Schema::create('division_saving_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('transaction_code', 40)->unique();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->foreignUlid('wallet_id')->constrained('division_wallets')->cascadeOnDelete();
            $table->enum('transaction_type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->string('description', 255);
            $table->string('reference_number', 100)->nullable();
            $table->string('proof_attachment_path', 2048)->nullable();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['division_id', 'transaction_date']);
            $table->index(['wallet_id', 'created_at']);
            $table->index(['division_id', 'transaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('division_saving_transactions');
    }
};

