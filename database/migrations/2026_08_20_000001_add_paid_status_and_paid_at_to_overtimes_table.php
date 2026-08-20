<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modify enum status to include 'paid'
        DB::statement("ALTER TABLE overtimes MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending'");

        // 2. Add paid_at column if not exists
        Schema::table('overtimes', function (Blueprint $table) {
            if (!Schema::hasColumn('overtimes', 'paid_at')) {
                $table->datetime('paid_at')->nullable()->after('approval_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            if (Schema::hasColumn('overtimes', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
        });

        DB::statement("ALTER TABLE overtimes MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};
