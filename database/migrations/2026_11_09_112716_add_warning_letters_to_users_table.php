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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('splt')->default(false)->after('status');
            $table->boolean('sp1')->default(false)->after('splt');
            $table->boolean('sp2')->default(false)->after('sp1');
            $table->boolean('sp3')->default(false)->after('sp2');
            $table->boolean('cutoff')->default(false)->after('sp3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['splt', 'sp1', 'sp2', 'sp3', 'cutoff']);
        });
    }
};
