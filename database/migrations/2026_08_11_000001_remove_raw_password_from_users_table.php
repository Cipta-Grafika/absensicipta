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
        if (Schema::hasColumn('users', 'raw_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('raw_password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('users', 'raw_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('raw_password')->nullable()->after('password');
            });
        }
    }
};
