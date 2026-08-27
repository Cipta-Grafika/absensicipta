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
            $table->time('meal_min_start_time')->nullable()->after('meal_allowance');
            $table->decimal('meal_min_duration', 4, 2)->nullable()->after('meal_min_start_time');
            $table->string('meal_condition_type', 50)->nullable()->default('start_time_gte')->after('meal_min_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_rates', function (Blueprint $table) {
            $table->dropColumn(['meal_min_start_time', 'meal_min_duration', 'meal_condition_type']);
        });
    }
};
