<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safely alter the ENUM column using a raw DB statement
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'excused', 'sick', 'absent', 'wfh', 'leave') DEFAULT 'absent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM statuses
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'excused', 'sick', 'absent') DEFAULT 'absent'");
    }
};
