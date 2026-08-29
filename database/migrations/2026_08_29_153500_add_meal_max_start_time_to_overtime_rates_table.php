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
            $table->time('meal_max_start_time')->nullable()->default('18:00:00')->after('meal_min_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_rates', function (Blueprint $table) {
            $table->dropColumn(['meal_max_start_time']);
        });
    }
};
