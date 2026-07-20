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
        Schema::create('saving_summaries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('savings_id')->constrained('savings')->cascadeOnDelete();
            $table->decimal('total_mandatory', 15, 2)->default(0);
            $table->decimal('total_secondary', 15, 2)->default(0);
            $table->timestamps();

            // Ensure unique combination of user and saving program
            $table->unique(['user_id', 'savings_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_summaries');
    }
};
