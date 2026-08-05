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
        Schema::create('employee_monthly_stats', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
            $table->string('period', 7); // Format 'YYYY-MM' (e.g. '2026-08')
            $table->integer('total_present')->default(0);
            $table->integer('total_late')->default(0);
            $table->integer('total_early_minutes')->default(0);
            $table->decimal('avg_early_minutes', 8, 2)->default(0);
            $table->decimal('score', 10, 2)->default(0);
            $table->integer('rank')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period']);
            $table->index(['period', 'division_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_monthly_stats');
    }
};
