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
        Schema::create('division_wallets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['division_id', 'title'], 'uniq_div_wallet_title');
            $table->index(['division_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('division_wallets');
    }
};

