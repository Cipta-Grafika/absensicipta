<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\EmployeeMonthlyStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        $this->notifyLeaderboardUpdate($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        $this->notifyLeaderboardUpdate($attendance);
    }

    public function deleted(Attendance $attendance): void
    {
        $this->notifyLeaderboardUpdate($attendance);
    }

    private function notifyLeaderboardUpdate(Attendance $attendance): void
    {
        if (empty($attendance->date)) {
            return;
        }

        $period = Carbon::parse($attendance->date)->format('Y-m');
        EmployeeMonthlyStat::recalculateForPeriod($period);
        $time = time();
        Cache::put('leaderboard_last_updated_' . $period, $time);
        Cache::put('leaderboard_last_updated_global', $time);
    }
}
