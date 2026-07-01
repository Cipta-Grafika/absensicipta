<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Livewire\Traits\HasAttendanceSummary;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceComponent extends Component
{
    use AttendanceDetailTrait, HasAttendanceSummary;
    use WithPagination, InteractsWithBanner;

    #[On('print-report')]
    public function printReport()
    {
        return redirect()->route('admin.attendances.report', [
            'month' => $this->month,
            'week' => $this->week,
            'date' => $this->date,
            'division' => $this->division,
            'jobTitle' => $this->jobTitle,
        ]);
    }

    # filter
    public ?string $month = null;
    public ?string $week = null;
    public ?string $date = null;
    public ?string $division = null;
    public ?string $jobTitle = null;
    public ?string $search = null;

    public bool $editingAttendance = false;
    public array $formAttendance = [];
    
    public bool $viewingMonthlyDetail = false;
    public ?string $monthlyDetailUserId = null;

    public function showMonthlyDetail($userId)
    {
        \Illuminate\Support\Facades\Log::info("showMonthlyDetail triggered for user: " . $userId);
        $this->monthlyDetailUserId = $userId;
        $this->viewingMonthlyDetail = true;
    }

    public function editAttendance($userId, $date)
    {
        if (!auth()->user()->isAdmin) {
            return;
        }

        $user = User::findOrFail($userId);
        
        if (auth()->user()->group === 'admin' && $user->division_id !== auth()->user()->division_id) {
            abort(403);
        }

        $attendance = Attendance::where('user_id', $userId)->whereDate('date', $date)->first();

        $this->formAttendance = [
            'user_id' => $userId,
            'name' => $user->name,
            'nip' => $user->nip,
            'date' => $date,
            'time_in' => $attendance ? ($attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : null) : null,
            'time_out' => $attendance ? ($attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : null) : null,
            'shift_id' => $attendance ? $attendance->shift_id : null,
            'status' => $attendance ? $attendance->status : '-',
            'note' => $attendance ? $attendance->note : null,
        ];

        $this->viewingMonthlyDetail = false;
        $this->editingAttendance = true;
    }

    public function saveAttendance()
    {
        if (!auth()->user()->isAdmin) {
            return;
        }

        $user = User::findOrFail($this->formAttendance['user_id']);
        if (auth()->user()->group === 'admin' && $user->division_id !== auth()->user()->division_id) {
            abort(403);
        }

        $this->validate([
            'formAttendance.time_in' => 'nullable|date_format:H:i',
            'formAttendance.time_out' => 'nullable|date_format:H:i',
            'formAttendance.shift_id' => 'nullable|exists:shifts,id',
            'formAttendance.status' => 'required|string',
            'formAttendance.note' => 'nullable|string',
        ]);

        if ($this->formAttendance['status'] == '-') {
            $attendance = Attendance::where('user_id', $this->formAttendance['user_id'])
                ->whereDate('date', $this->formAttendance['date'])->first();
            if ($attendance) {
                $attendance->delete();
            }
        } else {
            Attendance::updateOrCreate(
                [
                    'user_id' => $this->formAttendance['user_id'],
                    'date' => $this->formAttendance['date'],
                ],
                [
                    'time_in' => !empty($this->formAttendance['time_in']) ? $this->formAttendance['time_in'] : null,
                    'time_out' => !empty($this->formAttendance['time_out']) ? $this->formAttendance['time_out'] : null,
                    'shift_id' => !empty($this->formAttendance['shift_id']) ? $this->formAttendance['shift_id'] : null,
                    'status' => $this->formAttendance['status'],
                    'note' => !empty($this->formAttendance['note']) ? $this->formAttendance['note'] : null,
                ]
            );
        }

        $this->editingAttendance = false;
        if ($this->month) {
            $this->viewingMonthlyDetail = true;
        }

        $this->banner('Absensi berhasil diperbarui!');
        
        $my = Carbon::parse($this->formAttendance['date']);
        $weekFormat = $my->copy()->startOfWeek()->format('Y-\WW');
        
        Cache::forget("attendance-{$this->formAttendance['user_id']}-{$this->formAttendance['date']}");
        Cache::forget("attendance-{$this->formAttendance['user_id']}-{$weekFormat}");
        Cache::forget("attendance-{$this->formAttendance['user_id']}-{$my->month}-{$my->year}");
    }

    public function cancelEditAttendance()
    {
        $this->editingAttendance = false;
        if ($this->month) {
            $this->viewingMonthlyDetail = true;
        }
    }

    public function mount()
    {
        $this->date = date('Y-m-d');
    }

    public function updating($key): void
    {
        if ($key === 'search' || $key === 'division' || $key === 'jobTitle') {
            $this->resetPage();
        }
        if ($key === 'month') {
            $this->resetPage();
            $this->week = null;
            $this->date = null;
        }
        if ($key === 'week') {
            $this->resetPage();
            $this->month = null;
            $this->date = null;
        }
        if ($key === 'date') {
            $this->resetPage();
            $this->month = null;
            $this->week = null;
        }
    }

    public function updated($property, $value)
    {
        if ($property === 'formAttendance.shift_id' && !empty($value)) {
            $shift = \App\Models\Shift::find($value);
            if ($shift) {
                $this->formAttendance['time_in'] = $shift->start_time ? Carbon::parse($shift->start_time)->format('H:i') : null;
                $this->formAttendance['time_out'] = $shift->end_time ? Carbon::parse($shift->end_time)->format('H:i') : null;
            }
        }
    }

    public function render()
    {
        $dates = [];
        if ($this->date) {
            $dates = [Carbon::parse($this->date)];
        } else if ($this->week) {
            $start = Carbon::parse($this->week)->startOfWeek();
            $end = Carbon::parse($this->week)->endOfWeek();
            $dates = $start->range($end)->toArray();
        } else if ($this->month) {
            $start = Carbon::parse($this->month)->startOfMonth();
            $end = Carbon::parse($this->month)->endOfMonth();
            $dates = $start->range($end)->toArray();
        }
        $employees = User::where('group', 'user')
            ->when(auth()->user()->group === 'admin', fn (Builder $q) => $q->where('division_id', auth()->user()->division_id))
            ->when($this->search, function (Builder $q) {
                return $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('nip', 'like', '%' . $this->search . '%');
            })
            ->when($this->division, fn (Builder $q) => $q->where('division_id', $this->division))
            ->when($this->jobTitle, fn (Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->paginate(20)->through(function (User $user) {
                if ($this->date) {
                    $attendances = new Collection(Cache::remember(
                        "attendance-$user->id-$this->date",
                        now()->addDay(),
                        function () use ($user) {
                            /** @var Collection<Attendance>  */
                            $attendances = Attendance::filter(
                                userId: $user->id,
                                date: $this->date,
                            )->get();

                            return $attendances->map(
                                function (Attendance $v) {
                                    $v->setAttribute('coordinates', $v->lat_lng);
                                    $v->setAttribute('lat', $v->latitude);
                                    $v->setAttribute('lng', $v->longitude);
                                    if ($v->attachment) {
                                        $v->setAttribute('attachment', $v->attachment_url);
                                    }
                                    if ($v->shift) {
                                        $v->setAttribute('shift', $v->shift->name);
                                    }
                                    return $v->getAttributes();
                                }
                            )->toArray();
                        }
                    ) ?? []);
                } else if ($this->week) {
                    $attendances = new Collection(Cache::remember(
                        "attendance-$user->id-$this->week",
                        now()->addDay(),
                        function () use ($user) {
                            /** @var Collection<Attendance>  */
                            $attendances = Attendance::filter(
                                userId: $user->id,
                                week: $this->week,
                            )->get(['id', 'status', 'date', 'latitude', 'longitude', 'attachment', 'note']);

                            return $attendances->map(
                                function (Attendance $v) {
                                    $v->setAttribute('coordinates', $v->lat_lng);
                                    $v->setAttribute('lat', $v->latitude);
                                    $v->setAttribute('lng', $v->longitude);
                                    if ($v->attachment) {
                                        $v->setAttribute('attachment', $v->attachment_url);
                                    }
                                    return $v->getAttributes();
                                }
                            )->toArray();
                        }
                    ) ?? []);
                } else if ($this->month) {
                    $my = Carbon::parse($this->month);
                    $attendances = new Collection(Cache::remember(
                        "attendance-$user->id-$my->month-$my->year",
                        now()->addDay(),
                        function () use ($user) {
                            /** @var Collection<Attendance>  */
                            $attendances = Attendance::filter(
                                month: $this->month,
                                userId: $user->id,
                            )->get(['id', 'status', 'date', 'latitude', 'longitude', 'attachment', 'note']);

                            return $attendances->map(
                                function (Attendance $v) {
                                    $v->setAttribute('coordinates', $v->lat_lng);
                                    $v->setAttribute('lat', $v->latitude);
                                    $v->setAttribute('lng', $v->longitude);
                                    if ($v->attachment) {
                                        $v->setAttribute('attachment', $v->attachment_url);
                                    }
                                    return $v->getAttributes();
                                }
                            )->toArray();
                        }
                    ) ?? []);
                } else {
                    /** @var Collection */
                    $attendances = Attendance::where('user_id', $user->id)
                        ->get(['id', 'status', 'date', 'latitude', 'longitude', 'attachment', 'note']);
                }
                $user->attendances = $attendances;
                return $user;
            });
        $summary = $this->getAttendanceSummary($this->date, $this->week, $this->month);

        return view('livewire.admin.attendance', array_merge($summary, [
            'employees' => $employees, 
            'dates' => $dates
        ]));
    }
}
