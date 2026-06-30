<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class DashboardComponent extends Component
{
    use AttendanceDetailTrait;

    #[Url]
    public $date = '';

    #[Url]
    public $week = '';

    #[Url]
    public $month = '';

    public function render()
    {
        $queryDateStart = now()->startOfDay();
        $queryDateEnd = now()->endOfDay();
        $prevDateStart = now()->subDay()->startOfDay();
        $prevDateEnd = now()->subDay()->endOfDay();
        $filterText = 'vs hari lalu';
        $titlePrefix = 'Hari Ini';
        $isTodayOnly = true;

        if ($this->date) {
            $parsedDate = \Carbon\Carbon::parse($this->date);
            $queryDateStart = $parsedDate->copy()->startOfDay();
            $queryDateEnd = $parsedDate->copy()->endOfDay();
            $prevDateStart = $parsedDate->copy()->subDay()->startOfDay();
            $prevDateEnd = $parsedDate->copy()->subDay()->endOfDay();
            $filterText = 'vs hari lalu';
            $titlePrefix = $parsedDate->format('d M Y');
            $isTodayOnly = $parsedDate->isToday();
        } elseif ($this->week) {
            $parsedWeek = \Carbon\Carbon::parse($this->week);
            $queryDateStart = $parsedWeek->copy()->startOfWeek();
            $queryDateEnd = $parsedWeek->copy()->endOfWeek();
            $prevDateStart = $parsedWeek->copy()->subWeek()->startOfWeek();
            $prevDateEnd = $parsedWeek->copy()->subWeek()->endOfWeek();
            $filterText = 'vs mgg lalu';
            $titlePrefix = 'Minggu Ini';
            $isTodayOnly = false;
        } elseif ($this->month) {
            $parsedMonth = \Carbon\Carbon::parse($this->month);
            $queryDateStart = $parsedMonth->copy()->startOfMonth();
            $queryDateEnd = $parsedMonth->copy()->endOfMonth();
            $prevDateStart = $parsedMonth->copy()->subMonth()->startOfMonth();
            $prevDateEnd = $parsedMonth->copy()->subMonth()->endOfMonth();
            $filterText = 'vs bln lalu';
            $titlePrefix = 'Bulan ' . $parsedMonth->translatedFormat('F Y');
            $isTodayOnly = false;
        }

        // Fetch current period attendances
        $currentAttendances = Attendance::whereBetween('date', [$queryDateStart->format('Y-m-d'), $queryDateEnd->format('Y-m-d')])
            ->whereHas('user', function ($q) {
                if (auth()->user()->group === 'admin') {
                    $q->where('division_id', auth()->user()->division_id);
                }
            })
            ->get();

        // Fetch last period attendances
        $lastAttendances = Attendance::whereBetween('date', [$prevDateStart->format('Y-m-d'), $prevDateEnd->format('Y-m-d')])
            ->whereHas('user', function ($q) {
                if (auth()->user()->group === 'admin') {
                    $q->where('division_id', auth()->user()->division_id);
                }
            })
            ->get();

        $employeesCount = User::where('group', 'user')
            ->when(auth()->user()->group === 'admin', fn ($q) => $q->where('division_id', auth()->user()->division_id))
            ->count();

        // Count for the current period (for display in cards)
        $presentCount = $currentAttendances->whereIn('status', ['present', 'late'])->count();
        $lateCount = $currentAttendances->where('status', 'late')->count();
        $excusedCount = $currentAttendances->where('status', 'excused')->count();
        $sickCount = $currentAttendances->where('status', 'sick')->count();
        $wfhCount = $currentAttendances->where('status', 'wfh')->count();
        $leaveCount = $currentAttendances->where('status', 'leave')->count();

        // Calculate absences
        if ($isTodayOnly) {
            $absentCount = $employeesCount - ($presentCount + $excusedCount + $sickCount + $wfhCount + $leaveCount);
        } else {
            // For past periods or ranges, we rely on explicitly marked absent statuses 
            // OR we calculate based on expected attendances if we assume 1 record per employee per day
            $absentCount = $currentAttendances->where('status', 'absent')->count();
        }

        // For the previous period (to calculate trends)
        $prevPresentCount = $lastAttendances->whereIn('status', ['present', 'late'])->count();
        $prevExcusedCount = $lastAttendances->where('status', 'excused')->count();
        $prevSickCount = $lastAttendances->where('status', 'sick')->count();
        $prevWfhCount = $lastAttendances->where('status', 'wfh')->count();
        $prevLeaveCount = $lastAttendances->where('status', 'leave')->count();
        $prevAbsentCount = $lastAttendances->where('status', 'absent')->count();

        // If comparing yesterday and yesterday is entirely in the past, its absences should be in the DB.
        // But if there are no records, it defaults to 0.

        $stats = [
            'present' => ['current' => $presentCount, 'last' => $prevPresentCount],
            'excused' => ['current' => $excusedCount, 'last' => $prevExcusedCount],
            'sick'    => ['current' => $sickCount, 'last' => $prevSickCount],
            'wfh'     => ['current' => $wfhCount, 'last' => $prevWfhCount],
            'leave'   => ['current' => $leaveCount, 'last' => $prevLeaveCount],
            'absent'  => ['current' => $absentCount, 'last' => $prevAbsentCount],
        ];

        foreach ($stats as $key => $val) {
            $diff = $val['current'] - $val['last'];
            
            // Format percentage diff if needed, or raw diff
            if ($val['last'] > 0) {
                $pct = round(($diff / $val['last']) * 100);
                $stats[$key]['trend'] = $diff > 0 ? "+$pct%" : "$pct%";
            } else {
                $stats[$key]['trend'] = $diff > 0 ? "+$diff" : (string)$diff;
            }
            
            $stats[$key]['is_up'] = $diff > 0;
            $stats[$key]['is_same'] = $diff == 0;
            $stats[$key]['is_down'] = $diff < 0;
        }

        // For the employees table, we show today's status or the filtered date's status.
        // If it's week or month, showing the table of employees with just 1 status doesn't make sense.
        // We will just pass the employees with their first attendance matching the query date (if daily).
        $employees = User::where('group', 'user')
            ->when(auth()->user()->group === 'admin', fn ($q) => $q->where('division_id', auth()->user()->division_id))
            ->paginate(20)
            ->through(function (User $user) use ($currentAttendances) {
                return $user->setAttribute(
                    'attendance',
                    $currentAttendances
                        ->where(fn (Attendance $attendance) => $attendance->user_id === $user->id)
                        ->first(),
                );
            });

        return view('livewire.admin.dashboard', [
            'employees' => $employees,
            'employeesCount' => $employeesCount,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'excusedCount' => $excusedCount,
            'sickCount' => $sickCount,
            'wfhCount' => $wfhCount,
            'leaveCount' => $leaveCount,
            'absentCount' => $absentCount,
            'stats' => $stats,
            'filterText' => $filterText,
            'titlePrefix' => $titlePrefix,
        ]);
    }
}
