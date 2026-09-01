<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include 'owner'
        DB::statement("ALTER TABLE users MODIFY COLUMN `group` ENUM('user', 'admin', 'superadmin', 'payroll', 'syirkah', 'owner') DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN `group` ENUM('user', 'admin', 'superadmin', 'payroll', 'syirkah') DEFAULT 'user'");
    }
};
