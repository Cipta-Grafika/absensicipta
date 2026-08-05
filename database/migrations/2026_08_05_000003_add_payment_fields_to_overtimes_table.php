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
        Schema::table('overtimes', function (Blueprint $table) {
            $table->decimal('applied_rate_amount', 12, 2)->nullable()->after('duration_hours');
            $table->decimal('total_pay', 12, 2)->nullable()->after('applied_rate_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn(['applied_rate_amount', 'total_pay']);
        });
    }
};
