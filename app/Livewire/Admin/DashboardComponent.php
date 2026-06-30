<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class DashboardComponent extends Component
{
    use AttendanceDetailTrait;

    public function render()
    {
        /** @var Collection<Attendance>  */
        $attendances = Attendance::where('date', date('Y-m-d'))
            ->whereHas('user', function ($q) {
                if (auth()->user()->group === 'admin') {
                    $q->where('division_id', auth()->user()->division_id);
                }
            })
            ->get();

        /** @var Collection<User>  */
        $employees = User::where('group', 'user')
            ->when(auth()->user()->group === 'admin', fn ($q) => $q->where('division_id', auth()->user()->division_id))
            ->paginate(20)
            ->through(function (User $user) use ($attendances) {
                return $user->setAttribute(
                    'attendance',
                    $attendances
                        ->where(fn (Attendance $attendance) => $attendance->user_id === $user->id)
                        ->first(),
                );
            });

        $employeesCount = User::where('group', 'user')
            ->when(auth()->user()->group === 'admin', fn ($q) => $q->where('division_id', auth()->user()->division_id))
            ->count();
            
        $presentCount = $attendances->where(fn ($attendance) => $attendance->status === 'present')->count();
        $lateCount = $attendances->where(fn ($attendance) => $attendance->status === 'late')->count();
        $excusedCount = $attendances->where(fn ($attendance) => $attendance->status === 'excused')->count();
        $sickCount = $attendances->where(fn ($attendance) => $attendance->status === 'sick')->count();
        $absentCount = $employeesCount - ($presentCount + $lateCount + $excusedCount + $sickCount);

        // Fetch Monthly stats for comparison
        $currentMonthAttendances = Attendance::whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->whereHas('user', function ($q) {
                if (auth()->user()->group === 'admin') {
                    $q->where('division_id', auth()->user()->division_id);
                }
            })
            ->get();

        $lastMonthAttendances = Attendance::whereMonth('date', date('m', strtotime('first day of last month')))
            ->whereYear('date', date('Y', strtotime('first day of last month')))
            ->whereHas('user', function ($q) {
                if (auth()->user()->group === 'admin') {
                    $q->where('division_id', auth()->user()->division_id);
                }
            })
            ->get();

        $stats = [
            'present' => [
                'current' => $currentMonthAttendances->whereIn('status', ['present', 'late'])->count(),
                'last' => $lastMonthAttendances->whereIn('status', ['present', 'late'])->count(),
            ],
            'excused' => [
                'current' => $currentMonthAttendances->where('status', 'excused')->count(),
                'last' => $lastMonthAttendances->where('status', 'excused')->count(),
            ],
            'sick' => [
                'current' => $currentMonthAttendances->where('status', 'sick')->count(),
                'last' => $lastMonthAttendances->where('status', 'sick')->count(),
            ],
            'absent' => [
                'current' => $currentMonthAttendances->where('status', 'absent')->count(),
                'last' => $lastMonthAttendances->where('status', 'absent')->count(),
            ],
        ];

        foreach ($stats as $key => $val) {
            $diff = $val['current'] - $val['last'];
            $stats[$key]['trend'] = $diff > 0 ? "+$diff" : (string)$diff;
            $stats[$key]['is_up'] = $diff > 0;
            $stats[$key]['is_same'] = $diff == 0;
            $stats[$key]['is_down'] = $diff < 0;
        }

        return view('livewire.admin.dashboard', [
            'employees' => $employees,
            'employeesCount' => $employeesCount,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'excusedCount' => $excusedCount,
            'sickCount' => $sickCount,
            'absentCount' => $absentCount,
            'stats' => $stats,
        ]);
    }
}
