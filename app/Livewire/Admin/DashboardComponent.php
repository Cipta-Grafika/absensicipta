<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Livewire\Traits\HasAttendanceSummary;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardComponent extends Component
{
    use AttendanceDetailTrait, HasAttendanceSummary;
    use WithPagination;


    #[Url]
    public $date = '';

    #[Url]
    public $week = '';

    #[Url]
    public $month = '';

    public function updating($key): void
    {
        if ($key === 'month') {
            $this->resetPage();
            $this->week = '';
            $this->date = '';
        }
        if ($key === 'week') {
            $this->resetPage();
            $this->month = '';
            $this->date = '';
        }
        if ($key === 'date') {
            $this->resetPage();
            $this->month = '';
            $this->week = '';
        }
    }

    public function render()
    {
        $summary = $this->getAttendanceSummary($this->date, $this->week, $this->month);
        
        $currentAttendances = $summary['currentAttendances'];

        // For the employees table, we show today's status or the filtered date's status.
        $employees = User::where('group', 'user')
            ->whereIn('status', ['active', 'suspend'])
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
