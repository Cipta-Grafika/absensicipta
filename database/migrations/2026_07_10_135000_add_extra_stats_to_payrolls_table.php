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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('total_sick')->default(0)->after('total_absent');
            $table->integer('total_excused')->default(0)->after('total_sick');
            $table->integer('penalized_cuti_days')->default(0)->after('total_excused');
            $table->integer('late_days_count')->default(0)->after('total_late_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['total_sick', 'total_excused', 'penalized_cuti_days', 'late_days_count']);
        });
    }
};
