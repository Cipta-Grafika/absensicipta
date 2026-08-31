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
        Schema::create('tax_masters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('category')->default('TER A'); // TER A, TER B, TER C
            $table->string('code')->unique(); // e.g. TER-A-01, TER-A-02
            $table->string('name'); // e.g. TER A - Sampai dengan Rp 5.400.000 (0%)
            $table->decimal('min_gross_income', 15, 2)->default(0);
            $table->decimal('max_gross_income', 15, 2)->nullable();
            $table->decimal('rate_percentage', 5, 2)->default(0); // e.g. 0.25, 0.50, 1.00
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_masters');
    }
};
