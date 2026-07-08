<?php

use App\Models\ReplacementHour;
use App\Models\Attendance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $replacements = ReplacementHour::where('status', 'approved')->get();
        
        foreach($replacements as $rep) {
            $totalMinutes = ReplacementHour::where('user_id', $rep->user_id)
                ->where('replaced_date', $rep->replaced_date)
                ->where('status', 'approved')
                ->get()
                ->sum('duration_minutes');
            
            Attendance::where('user_id', $rep->user_id)
                ->whereDate('date', $rep->replaced_date)
                ->update(['replaced_duration_hours' => floor($totalMinutes / 60)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed
    }
};
