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
        Schema::create('overtime_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('min_hours', 5, 2)->default(0);
            $table->decimal('max_hours', 5, 2)->default(24);
            $table->decimal('rate_amount', 12, 2)->default(0);
            $table->enum('rate_type', ['per_hour', 'flat_package'])->default('per_hour');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_rates');
    }
};
