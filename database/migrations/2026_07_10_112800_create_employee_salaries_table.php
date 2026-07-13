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
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('users')->onDelete('cascade');
            $table->enum('salary_type', ['monthly', 'daily'])->default('monthly');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('overtime_rate', 15, 2)->default(0);
            $table->decimal('meal_allowance', 15, 2)->nullable();
            $table->decimal('transport_allowance', 15, 2)->nullable();
            $table->decimal('attendance_allowance', 15, 2)->nullable();
            $table->decimal('late_deduction_rate', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
