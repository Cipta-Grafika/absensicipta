<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeMonthlyStat extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'employee_monthly_stats';

    protected $fillable = [
        'user_id',
        'division_id',
        'period',
        'total_present',
        'total_late',
        'total_early_minutes',
        'avg_early_minutes',
        'score',
        'rank',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /**
     * Recalculate monthly stats for a given period (format 'YYYY-MM') for all employees or a specific user.
     */
    public static function recalculateForPeriod(string $period, ?string $userId = null): void
    {
        $start = Carbon::parse($period . '-01')->startOfMonth();
        $end = Carbon::parse($period . '-01')->endOfMonth();

        $usersQuery = User::where('group', 'user')->whereIn('status', ['active', 'suspend']);
        if ($userId) {
            $usersQuery->where('id', $userId);
        }
        $users = $usersQuery->get();

        foreach ($users as $u) {
            $attendances = Attendance::where('user_id', $u->id)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->with('shift')
                ->get();

            $totalPresent = $attendances->whereIn('status', ['present', 'late', 'wfh'])->count();
            $totalLate = $attendances->where('status', 'late')->count();

            $totalEarlyMinutes = 0;
            $earlyCount = 0;

            foreach ($attendances as $att) {
                if ($att->shift && $att->time_in && in_array($att->status, ['present', 'late'])) {
                    $attDate = Carbon::parse($att->date)->format('Y-m-d');
                    $timeInStr = Carbon::parse($att->time_in)->format('H:i:s');
                    $shiftStartStr = Carbon::parse($att->shift->start_time)->format('H:i:s');

                    $timeIn = Carbon::parse($attDate . ' ' . $timeInStr);
                    $shiftStart = Carbon::parse($attDate . ' ' . $shiftStartStr);

                    if ($timeIn->lessThan($shiftStart)) {
                        $earlyMins = $timeIn->diffInMinutes($shiftStart);
                        $totalEarlyMinutes += $earlyMins;
                        $earlyCount++;
                    }
                }
            }

            $avgEarlyMinutes = $earlyCount > 0 ? round($totalEarlyMinutes / $earlyCount, 2) : 0;

            // Score calculation: (total_present * 10) + (total_early_minutes * 0.5) - (total_late * 5)
            $score = ($totalPresent * 10) + ($totalEarlyMinutes * 0.5) - ($totalLate * 5);
            if ($score < 0) {
                $score = 0;
            }

            static::updateOrCreate(
                [
                    'user_id' => $u->id,
                    'period' => $period,
                ],
                [
                    'division_id' => $u->division_id,
                    'total_present' => $totalPresent,
                    'total_late' => $totalLate,
                    'total_early_minutes' => $totalEarlyMinutes,
                    'avg_early_minutes' => $avgEarlyMinutes,
                    'score' => $score,
                ]
            );
        }

        // Update ranks per period
        $stats = static::where('period', $period)
            ->orderByDesc('score')
            ->orderByDesc('total_present')
            ->orderByDesc('total_early_minutes')
            ->get();
        $rank = 1;
        foreach ($stats as $st) {
            $st->update(['rank' => $rank++]);
        }
    }
}
