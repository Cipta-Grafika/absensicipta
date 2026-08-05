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
        return redirect()->route('hr.attendances.report', [
            'month' => $this->month,
            'week' => $this->week,
            'date' => $this->date,
            'attendanceStatus' => $this->attendanceStatus,
            'division' => $this->division,
            'jobTitle' => $this->jobTitle,
            'search' => $this->search,
        ]);
    }

    # filter
    public ?string $month = null;
    public ?string $week = null;
    public ?string $date = null;
    public ?string $attendanceStatus = null;
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
            'imp_duration_minutes' => $attendance && $attendance->imp_duration_minutes ? floor($attendance->imp_duration_minutes / 60).':'.str_pad($attendance->imp_duration_minutes % 60, 2, '0', STR_PAD_LEFT) : null,
            'note' => $attendance ? $attendance->note : null,
        ];

        // Calculate replaced hours from replacement_hours table
        $replacedHoursRecords = \App\Models\ReplacementHour::where('user_id', $userId)
            ->where('replaced_date', $date)
            ->where('status', 'approved')
            ->get();
            
        $replacedMinutes = $replacedHoursRecords->sum('duration_minutes');
        $dbReplacedMinutes = $attendance && $attendance->replaced_duration_minutes !== null 
            ? $attendance->replaced_duration_minutes 
            : $replacedMinutes;
            
        $this->formAttendance['replaced_duration_minutes'] = $dbReplacedMinutes > 0 
            ? floor($dbReplacedMinutes / 60) . ':' . str_pad($dbReplacedMinutes % 60, 2, '0', STR_PAD_LEFT) 
            : null;

        $this->viewingMonthlyDetail = false;
        $this->editingAttendance = true;
    }

    private function parseHHMM($string) {
        if (!$string) return null;
        if (preg_match('/^([0-9]+):([0-5][0-9])$/', $string, $matches)) {
            return ($matches[1] * 60) + $matches[2];
        }
        return null;
    }

    public function saveAttendance()
    {
        if (!auth()->user()->isAdmin) {
            return;
        }

        if (!auth()->user()->isSuperadmin) {
            abort(403, 'Forbidden. Only Superadmin can modify attendance.');
        }

        $user = User::findOrFail($this->formAttendance['user_id']);
        if (auth()->user()->group === 'admin' && $user->division_id !== auth()->user()->division_id) {
            abort(403);
        }

        foreach (['time_in', 'time_out', 'shift_id', 'imp_duration_minutes', 'replaced_duration_minutes', 'note'] as $key) {
            if (isset($this->formAttendance[$key]) && trim($this->formAttendance[$key]) === '') {
                $this->formAttendance[$key] = null;
            }
        }

        $this->validate([
            'formAttendance.time_in' => 'nullable|date_format:H:i',
            'formAttendance.time_out' => 'nullable|date_format:H:i',
            'formAttendance.shift_id' => 'nullable|exists:shifts,id',
            'formAttendance.status' => 'required|string',
            'formAttendance.imp_duration_minutes' => 'nullable|string|regex:/^([0-9]+):([0-5][0-9])$/',
            'formAttendance.replaced_duration_minutes' => 'nullable|string|regex:/^([0-9]+):([0-5][0-9])$/',
            'formAttendance.note' => 'nullable|string',
        ]);

        if ($this->formAttendance['status'] == '-') {
            Attendance::where('user_id', $this->formAttendance['user_id'])
                ->whereDate('date', $this->formAttendance['date'])->delete();
        } else {
            $existing = Attendance::where('user_id', $this->formAttendance['user_id'])
                ->whereDate('date', $this->formAttendance['date'])->get();
                
            if ($existing->isNotEmpty()) {
                $first = $existing->first();
                // Hapus duplikat (jika ada lebih dari 1 record di hari yang sama)
                $existing->where('id', '!=', $first->id)->each->delete();
            }
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
                    'imp_duration_minutes' => isset($this->formAttendance['imp_duration_minutes']) ? $this->parseHHMM($this->formAttendance['imp_duration_minutes']) : null,
                    'replaced_duration_minutes' => isset($this->formAttendance['replaced_duration_minutes']) ? $this->parseHHMM($this->formAttendance['replaced_duration_minutes']) : null,
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
        if ($key === 'search' || $key === 'division' || $key === 'jobTitle' || $key === 'attendanceStatus') {
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
            ->whereIn('status', ['active', 'suspend'])
            ->when(auth()->user()->group === 'admin', fn (Builder $q) => $q->where('division_id', auth()->user()->division_id))
            ->when($this->search, function (Builder $q) {
                return $q->where(function (Builder $query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('nip', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->division, function (Builder $q) {
                if (auth()->user()->group === 'admin' && $this->division != auth()->user()->division_id) {
                    return $q->whereRaw('1 = 0');
                }
                return $q->where('division_id', $this->division);
            })
            ->when($this->jobTitle, fn (Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->when($this->attendanceStatus, function (Builder $q) {
                $status = $this->attendanceStatus;
                $date = $this->date;
                $week = $this->week;
                $month = $this->month;

                if ($date) {
                    $rangeStart = Carbon::parse($date)->startOfDay();
                    $rangeEnd = Carbon::parse($date)->endOfDay();
                } elseif ($week) {
                    $rangeStart = Carbon::parse($week)->startOfWeek();
                    $rangeEnd = Carbon::parse($week)->endOfWeek();
                } elseif ($month) {
                    $rangeStart = Carbon::parse($month)->startOfMonth();
                    $rangeEnd = Carbon::parse($month)->endOfMonth();
                } else {
                    $rangeStart = now()->startOfDay();
                    $rangeEnd = now()->endOfDay();
                }

                $q->where(function (Builder $subQ) use ($status, $rangeStart, $rangeEnd, $date) {
                    $subQ->whereHas('attendances', function (Builder $attQ) use ($status, $rangeStart, $rangeEnd) {
                        $attQ->where('status', $status)
                            ->whereBetween('date', [$rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')]);
                    });

                    if ($status === 'absent') {
                        $pastEnd = $rangeEnd->gt(now()) ? now()->endOfDay() : $rangeEnd;

                        if ($rangeStart->lte($pastEnd)) {
                            $subQ->orWhereDoesntHave('attendances', function (Builder $attQ) use ($rangeStart, $pastEnd) {
                                $attQ->whereBetween('date', [$rangeStart->format('Y-m-d'), $pastEnd->format('Y-m-d')]);
                            });

                            if (!$date && $rangeStart->lt($pastEnd->copy()->startOfDay())) {
                                $daysDiff = $rangeStart->diffInDays($pastEnd->copy()->startOfDay()) + 1;
                                $subQ->orWhereHas('attendances', function (Builder $attQ) use ($rangeStart, $pastEnd) {
                                    $attQ->whereBetween('date', [$rangeStart->format('Y-m-d'), $pastEnd->format('Y-m-d')]);
                                }, '<', $daysDiff);
                            }
                        }
                    }
                });
            })
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
                            )->get(['id', 'status', 'date', 'latitude', 'longitude', 'attachment', 'note', 'imp_duration_minutes', 'replaced_duration_minutes']);

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
                            )->get(['id', 'status', 'date', 'latitude', 'longitude', 'attachment', 'note', 'imp_duration_minutes', 'replaced_duration_minutes']);

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
                        ->get(['id', 'status', 'date', 'latitude', 'longitude', 'attachment', 'note', 'imp_duration_minutes', 'replaced_duration_minutes']);
                }
                $user->attendances = $attendances;
                return $user;
            });
        $summary = $this->getAttendanceSummary($this->date, $this->week, $this->month, $this->search, $this->division, $this->jobTitle, $this->attendanceStatus);

        return view('livewire.admin.attendance', array_merge($summary, [
            'employees' => $employees, 
            'dates' => $dates
        ]));
    }
}
