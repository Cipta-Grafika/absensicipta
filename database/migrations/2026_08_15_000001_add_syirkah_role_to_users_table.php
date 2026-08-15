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
        // Modify the enum to include 'syirkah'
        DB::statement("ALTER TABLE users MODIFY COLUMN `group` ENUM('user', 'admin', 'superadmin', 'payroll', 'syirkah') DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN `group` ENUM('user', 'admin', 'superadmin', 'payroll') DEFAULT 'user'");
    }
};
