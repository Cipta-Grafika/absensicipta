<?php

use App\Models\Attendance;
use App\Models\EmployeeLeaveBalance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find all distinct years in attendances table
        $years = Attendance::whereNotNull('date')
            ->selectRaw('DISTINCT YEAR(date) as yr')
            ->pluck('yr')
            ->filter()
            ->map(fn($y) => (int) $y)
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
        }

        foreach ($years as $year) {
            EmployeeLeaveBalance::syncAllForYear($year);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed on down
    }
};
