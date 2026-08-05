<?php

namespace App\Livewire;

use App\ExtendedCarbon;
use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Services\AttendanceScheduleService;
use Ballen\Distical\Calculator as DistanceCalculator;
use Ballen\Distical\Entities\LatLong;
use Illuminate\Support\Carbon;

class ScanComponent extends Component
{
    public ?Attendance $attendance = null;
    public $shift_id = null;
    public $shifts = null;
    public ?array $currentLiveCoords = null;
    public string $successMsg = '';
    public bool $isAbsence = false;
    public bool $showMotivationModal = false;
    public bool $showLocationMapModal = false;
    public string $motivationTitle = '';
    public string $motivationMessage = '';
    public string $motivationType = '';

    public function scan(string $barcode)
    {
        if (is_null($this->currentLiveCoords)) {
            return __('Invalid location');
        } else if (is_null($this->shift_id)) {
            return __('Invalid shift');
        }

        /** @var Barcode */
        $barcode = Barcode::firstWhere('value', $barcode);
        if (!Auth::check() || !$barcode) {
            return 'Invalid barcode';
        }

        $barcodeLocation = new LatLong($barcode->latLng['lat'], $barcode->latLng['lng']);
        $userLocation = new LatLong($this->currentLiveCoords[0], $this->currentLiveCoords[1]);

        if (($distance = $this->calculateDistance($userLocation, $barcodeLocation)) > $barcode->radius) {
            return __('Location out of range') . ": $distance" . "m. Max: $barcode->radius" . "m";
        }

        /** @var Attendance */
        $existingAttendance = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        if (!$existingAttendance) {
            $attendance = $this->createAttendance($barcode);
            $this->successMsg = __('Attendance In Successful');

            $shift = Shift::find($this->shift_id);
            $shiftTime = Carbon::today()->setTimeFromTimeString($shift->start_time);
            $now = Carbon::now();
            $diffMinutes = $now->diffInMinutes($shiftTime, false);
            
            $userName = explode(' ', trim(Auth::user()->name))[0]; // Get first name
            if ($diffMinutes > 60) {
                $this->motivationType = 'early';
                $this->motivationTitle = 'Luar Biasa!';
                $this->motivationMessage = "Gokill {$userName}! Kamu awal banget.";
            } elseif ($diffMinutes >= 30) {
                $this->motivationType = 'early';
                $this->motivationTitle = 'Hebat!';
                $this->motivationMessage = "Hebat {$userName}! Datang lebih awal nih.";
            } elseif ($diffMinutes >= 15) {
                $this->motivationType = 'early';
                $this->motivationTitle = 'Mantap!';
                $this->motivationMessage = "Siip {$userName}! Pertahankan waktu kamu.";
            } elseif ($diffMinutes >= 0) {
                $this->motivationType = 'on-time';
                $this->motivationTitle = 'Tepat Waktu!';
                $this->motivationMessage = "Tepat waktu {$userName}! Semangat kerjanya.";
            } else {
                $this->motivationType = 'late';
                $this->motivationTitle = 'Perhatian!';
                $this->motivationMessage = "Yaah {$userName}! Kamu telat nih!";
            }
            $this->showMotivationModal = true;
        } else {
            $attendance = $existingAttendance;
            $attendance->update([
                'time_out' => date('H:i:s'),
            ]);
            $this->successMsg = __('Attendance Out Successful');

            $userName = explode(' ', trim(Auth::user()->name))[0];
            $this->motivationType = 'out';
            $this->motivationTitle = 'Terima Kasih!';
            $this->motivationMessage = "Terima kasih atas kerja kerasmu hari ini, {$userName}! Selamat beristirahat.";
            $this->showMotivationModal = true;
        }

        if ($attendance) {
            $this->setAttendance($attendance->fresh());
            Attendance::clearUserAttendanceCache(Auth::user(), Carbon::parse($attendance->date));
            return true;
        }
    }

    public function closeMotivationModal()
    {
        $this->showMotivationModal = false;
    }

    public function calculateDistance(LatLong $a, LatLong $b)
    {
        $distanceCalculator = new DistanceCalculator($a, $b);
        $distanceInMeter = floor($distanceCalculator->get()->asKilometres() * 1000); // convert to meters
        return $distanceInMeter;
    }

    /** @return Attendance */
    public function createAttendance(Barcode $barcode)
    {
        $now = Carbon::now();
        $date = $now->format('Y-m-d');
        $timeIn = $now->format('H:i:s');
        /** @var Shift */
        $shift = Shift::find($this->shift_id);
        $status = Carbon::now()->setTimeFromTimeString($shift->start_time)->lt($now) ? 'late' : 'present';
        return Attendance::create([
            'user_id' => Auth::user()->id,
            'barcode_id' => $barcode->id,
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => null,
            'shift_id' => $shift->id,
            'latitude' => doubleval($this->currentLiveCoords[0]),
            'longitude' => doubleval($this->currentLiveCoords[1]),
            'status' => $status,
            'note' => null,
            'attachment' => null,
        ]);
    }

    protected function setAttendance(Attendance $attendance)
    {
        $this->attendance = $attendance;
        $this->shift_id = $attendance->shift_id;
        $this->isAbsence = $attendance->status !== 'present' && $attendance->status !== 'late';
    }

    public function getAttendance()
    {
        if (is_null($this->attendance)) {
            return null;
        }
        return [
            'time_in' => $this->attendance?->time_in,
            'time_out' => $this->attendance?->time_out,
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $this->shifts = Shift::forUser($user)->get();

        /** @var Attendance */
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', date('Y-m-d'))->first();
        if ($attendance) {
            $this->setAttendance($attendance);
        } else {
            if ($this->shifts->isNotEmpty()) {
                // Priority 1: User's division-specific shifts
                $divisionShifts = $this->shifts->filter(fn (Shift $s) => !is_null($s->division_id) && $s->division_id == $user->division_id);
                $candidateShifts = $divisionShifts->isNotEmpty() ? $divisionShifts : $this->shifts;

                $closest = ExtendedCarbon::now()
                    ->closestFromDateArray($candidateShifts->pluck('start_time')->toArray());

                $matchedShift = $candidateShifts
                    ->where(fn (Shift $shift) => $shift->start_time == $closest->format('H:i:s'))
                    ->first();

                $this->shift_id = $matchedShift?->id ?? $candidateShifts->first()?->id;
            }
        }
    }

    public function getRealtimeDeduction(): float
    {
        $user = Auth::user();
        if (!$user) return 0;

        $salary = $user->salary;
        if (!$salary) return 0;

        $startPeriod = Carbon::now()->startOfMonth();
        $today = Carbon::today();

        $startDateStr = $startPeriod->format('Y-m-d');
        $endDateStr = $today->format('Y-m-d');

        $scheduleContext = AttendanceScheduleService::buildContext([$user], $startDateStr, $endDateStr);

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->with('shift')
            ->get();

        $attendancesByDate = $attendances->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
        });

        $missing_absent_days = 0;
        $consecutive_cuti = 0;
        $penalized_cuti_days = 0;
        $late_days_count = 0;

        for ($d = $startPeriod->copy(); $d->lte($today); $d->addDay()) {
            if ($scheduleContext->isWorkingDay($user, $d)) {
                $records = $attendancesByDate->get($d->format('Y-m-d'), collect());
                $hasValidRecord = $records->whereNotIn('status', ['absent', 'dayoff'])->isNotEmpty();
                $isExplicitDayOff = $records->where('status', 'dayoff')->isNotEmpty();

                if (!$hasValidRecord && !$isExplicitDayOff) {
                    $missing_absent_days++;
                }

                if ($records->where('status', 'leave')->isNotEmpty()) {
                    $consecutive_cuti++;
                    if ($consecutive_cuti > 2) {
                        $penalized_cuti_days++;
                    }
                } else {
                    $consecutive_cuti = 0;
                }

                if ($records->where('status', 'late')->isNotEmpty()) {
                    $late_days_count++;
                }
            }
        }

        $days_divisor = $salary->working_days_per_month ?? 25;
        $fixed_income = $salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance;
        $daily_rate_approx = $days_divisor > 0 ? $fixed_income / $days_divisor : 0;

        $total_absent = $missing_absent_days + $attendances->where('status', 'absent')->count();
        $total_excused = $attendances->where('status', 'excused')->count();
        $total_sick = $attendances->where('status', 'sick')->count();
        $total_wfh = $attendances->where('status', 'wfh')->count();

        // Late Minutes
        $total_late_minutes = 0;
        foreach ($attendances->where('status', 'late') as $att) {
            if ($att->shift && $att->time_in) {
                $timeIn = Carbon::parse($att->time_in);
                $shiftStart = Carbon::parse($att->shift->start_time);
                if ($timeIn->greaterThan($shiftStart)) {
                    $total_late_minutes += $timeIn->diffInMinutes($shiftStart);
                }
            }
        }

        // Unreplaced IMP Minutes
        $total_unreplaced_imp_minutes = 0;
        foreach ($attendances->where('status', 'imp') as $att) {
            $imp_duration = $att->imp_duration_minutes ?? 0;
            $replaced = $att->replaced_duration_minutes ?? 0;
            $unreplaced = max(0, $imp_duration - $replaced);
            $total_unreplaced_imp_minutes += $unreplaced;
        }

        $late_rate = $salary->late_deduction_per_minute ?? $salary->late_deduction_rate ?? 0;
        $late_deduction = $total_late_minutes * $late_rate;
        $imp_deduction = ($days_divisor > 0) ? $total_unreplaced_imp_minutes * ($fixed_income / ($days_divisor * 8 * 60)) : 0;

        $effective_absent = min($total_absent, max(1, $days_divisor));
        $absent_deduction = $daily_rate_approx * $effective_absent;

        $effective_excused = min($total_excused, max(1, $days_divisor));
        $excused_deduction = ($days_divisor > 0) ? ($effective_excused / ($days_divisor * 2)) * $fixed_income + ($effective_excused / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance) : 0;

        $effective_sick = min($total_sick, max(1, $days_divisor));
        $sick_deduction = ($days_divisor > 0) ? ($effective_sick / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance) : 0;

        $effective_cuti = min($penalized_cuti_days, max(1, $days_divisor));
        $cuti_deduction = ($days_divisor > 0) ? ($effective_cuti / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance) : 0;

        $effective_wfh = min($total_wfh, max(1, $days_divisor));
        if ($user->count_wfo) {
            $wfh_deduction = 0;
        } else {
            $wfh_deduction = ($days_divisor > 0) ? ($effective_wfh / $days_divisor) * (0.5 * $fixed_income) : 0;
        }

        $late_penalty_deduction = ($late_days_count > 3) ? (0.10 * $salary->attendance_allowance) : 0;

        if ($salary->salary_type == 'daily') {
            $absent_deduction = 0;
            $excused_deduction = 0;
            $sick_deduction = 0;
            $cuti_deduction = 0;
            $wfh_deduction = 0;
        }

        $total_deduction = $absent_deduction + $late_deduction + $imp_deduction + $excused_deduction + $sick_deduction + $cuti_deduction + $wfh_deduction + $late_penalty_deduction;

        return round($total_deduction, 0);
    }

    public function render()
    {
        return view('livewire.scan');
    }
}
