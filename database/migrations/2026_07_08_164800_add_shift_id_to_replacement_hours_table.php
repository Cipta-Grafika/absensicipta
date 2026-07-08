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
        Schema::table('replacement_hours', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('end_hour')->constrained('shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replacement_hours', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });
    }
};
