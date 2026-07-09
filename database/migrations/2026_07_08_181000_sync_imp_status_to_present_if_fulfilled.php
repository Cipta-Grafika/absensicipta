<?php

use App\Models\ReplacementHour;
use App\Models\Attendance;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $attendances = Attendance::where('status', 'imp')
            ->whereNotNull('replaced_duration_hours')
            ->get();
            
        foreach($attendances as $attendance) {
            $isImpFulfilled = $attendance->imp_duration_hours > 0 && 
                $attendance->replaced_duration_hours >= $attendance->imp_duration_hours;
                
            $targetMinutes = $attendance->shift ? $attendance->shift->duration_minutes : 0;
            $isShiftFulfilled = $targetMinutes > 0 && ($attendance->replaced_duration_hours * 60) >= $targetMinutes;
            
            if ($isImpFulfilled || $isShiftFulfilled) {
                $attendance->update(['status' => 'present']);
            }
        }
    }

    public function down(): void
    {
    }
};
