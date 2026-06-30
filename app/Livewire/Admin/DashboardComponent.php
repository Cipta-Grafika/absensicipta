<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Livewire\Traits\HasAttendanceSummary;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class DashboardComponent extends Component
{
    use AttendanceDetailTrait, HasAttendanceSummary;

    #[Url]
    public $date = '';

    #[Url]
    public $week = '';

    #[Url]
    public $month = '';

    public function render()
    {
        $summary = $this->getAttendanceSummary($this->date, $this->week, $this->month);
        
        $currentAttendances = $summary['currentAttendances'];

        // For the employees table, we show today's status or the filtered date's status.
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

        return view('livewire.admin.dashboard', array_merge($summary, [
            'employees' => $employees,
        ]));
    }
}
