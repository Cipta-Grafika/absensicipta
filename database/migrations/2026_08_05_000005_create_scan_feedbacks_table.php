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
        Schema::create('scan_feedbacks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('category', 50); // super_early, early, on_time, late_mild, late_severe, out
            $table->string('title', 150);
            $table->text('message');
            $table->string('icon', 50)->default('sparkles');
            $table->string('badge_color', 50)->default('green');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_feedbacks');
    }
};
