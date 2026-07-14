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
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('imp_duration_minutes')->nullable()->after('imp_duration_hours');
            $table->integer('replaced_duration_minutes')->nullable()->after('replaced_duration_hours');
        });

        // Migrate data
        \Illuminate\Support\Facades\DB::table('attendances')->update([
            'imp_duration_minutes' => \Illuminate\Support\Facades\DB::raw('imp_duration_hours * 60'),
            'replaced_duration_minutes' => \Illuminate\Support\Facades\DB::raw('replaced_duration_hours * 60')
        ]);

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('imp_duration_hours');
            $table->dropColumn('replaced_duration_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('imp_duration_hours')->nullable()->after('imp_duration_minutes');
            $table->integer('replaced_duration_hours')->nullable()->after('replaced_duration_minutes');
        });

        // Revert data
        \Illuminate\Support\Facades\DB::table('attendances')->update([
            'imp_duration_hours' => \Illuminate\Support\Facades\DB::raw('FLOOR(imp_duration_minutes / 60)'),
            'replaced_duration_hours' => \Illuminate\Support\Facades\DB::raw('FLOOR(replaced_duration_minutes / 60)')
        ]);

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('imp_duration_minutes');
            $table->dropColumn('replaced_duration_minutes');
        });
    }
};
