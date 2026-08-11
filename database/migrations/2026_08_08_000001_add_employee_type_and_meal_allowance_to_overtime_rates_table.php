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
        Schema::table('overtime_rates', function (Blueprint $table) {
            $table->string('employee_type')->nullable()->default('all')->after('division_id');
            $table->decimal('meal_allowance', 12, 2)->nullable()->default(0.00)->after('employee_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_rates', function (Blueprint $table) {
            $table->dropColumn(['employee_type', 'meal_allowance']);
        });
    }
};
