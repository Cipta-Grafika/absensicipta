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
        Schema::create('saving_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('savings_id')->constrained('savings')->cascadeOnDelete();
            $table->enum('transaction_type', ['deposit', 'withdrawal']);
            $table->decimal('mandatory_amount', 15, 2)->default(0);
            $table->decimal('secondary_amount', 15, 2)->default(0);
            $table->decimal('balance_mandatory', 15, 2)->default(0);
            $table->decimal('balance_secondary', 15, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->ulid('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_transactions');
    }
};
