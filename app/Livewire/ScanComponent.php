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
use Laravel\Jetstream\InteractsWithBanner;

class ScanComponent extends Component
{
    use InteractsWithBanner;

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
        if (is_null($this->currentLiveCoords) || count($this->currentLiveCoords) < 2) {
            return __('Invalid location');
        } else if (is_null($this->shift_id)) {
            return __('Invalid shift');
        }

        /** @var Barcode */
        $barcodeModel = Barcode::firstWhere('value', $barcode);
        if (!Auth::check() || !$barcodeModel) {
            return 'Invalid barcode';
        }

        $barcodeLocation = new LatLong($barcodeModel->latLng['lat'], $barcodeModel->latLng['lng']);
        $userLocation = new LatLong($this->currentLiveCoords[0], $this->currentLiveCoords[1]);

        if (($distance = $this->calculateDistance($userLocation, $barcodeLocation)) > $barcodeModel->radius) {
            return __('Location out of range') . ": $distance" . "m. Max: $barcodeModel->radius" . "m";
        }

        /** @var Attendance */
        $existingAttendance = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        $isCheckInAction = !$existingAttendance || empty($existingAttendance->time_in);

        if ($isCheckInAction) {
            if ($existingAttendance) {
                $shift = Shift::find($this->shift_id);
                $shiftStartTime = $shift ? $shift->start_time : '08:00:00';
                $now = Carbon::now();
                $status = Carbon::now()->setTimeFromTimeString($shiftStartTime)->lt($now) ? 'late' : 'present';
                $existingAttendance->update([
                    'barcode_id' => $barcodeModel->id,
                    'shift_id' => $shift?->id,
                    'time_in' => date('H:i:s'),
                    'latitude' => doubleval($this->currentLiveCoords[0]),
                    'longitude' => doubleval($this->currentLiveCoords[1]),
                    'status' => $status,
                ]);
                $attendance = $existingAttendance;
            } else {
                $attendance = $this->createAttendance($barcodeModel);
            }

            $this->successMsg = __('Attendance In Successful');

            $shift = Shift::find($this->shift_id);
            $shiftStartTime = $shift ? $shift->start_time : '08:00:00';
            $shiftTime = Carbon::today()->setTimeFromTimeString($shiftStartTime);
            $now = Carbon::now();
            $diffMinutes = $now->diffInMinutes($shiftTime, false);
            
            $userName = explode(' ', trim(Auth::user()->name ?? 'Karyawan'))[0];

            if ($diffMinutes > 30) {
                $category = 'super_early';
            } elseif ($diffMinutes >= 15) {
                $category = 'early';
            } elseif ($diffMinutes >= 0) {
                $category = 'on_time';
            } elseif ($diffMinutes >= -15) {
                $category = 'late_mild';
            } else {
                $category = 'late_severe';
            }

            $feedback = \App\Models\ScanFeedback::getRandomFeedback($category, $userName);
            $this->motivationType = $feedback['type'];
            $this->motivationTitle = $feedback['title'];
            $this->motivationMessage = $feedback['message'];
            $this->showMotivationModal = true;
        } else {
            $attendance = $existingAttendance;
            $attendance->update([
                'time_out' => date('H:i:s'),
                'latitude_out' => doubleval($this->currentLiveCoords[0]),
                'longitude_out' => doubleval($this->currentLiveCoords[1]),
            ]);
            $this->successMsg = __('Attendance Out Successful');

            $userName = explode(' ', trim(Auth::user()->name ?? 'Karyawan'))[0];
            $feedback = \App\Models\ScanFeedback::getRandomFeedback('out', $userName);
            $this->motivationType = $feedback['type'];
            $this->motivationTitle = $feedback['title'];
            $this->motivationMessage = $feedback['message'];
            $this->showMotivationModal = true;
        }

        if ($attendance) {
            $this->setAttendance($attendance->fresh());
            Attendance::clearUserAttendanceCache(Auth::user(), Carbon::parse($attendance->date));
            return true;
        }
    }

    /**
     * Validate GPS coordinates for anti-spoofing / anti-fake-gps hardening.
     */
    protected function validateGpsHardening(float $lat, float $lng): ?string
    {
        // 1. Sanity check for valid latitude/longitude bounds
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return __('Absen Gagal: Format koordinat GPS tidak valid.');
        }

        // 2. Block Null Island (0,0) coordinate spoofing
        if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
            return __('Absen Gagal: Lokasi GPS terdeteksi tidak valid (Null Island).');
        }

        // 3. Teleportation / Impossible Speed Check against user's last attendance
        $user = Auth::user();
        if ($user) {
            $lastAttendance = Attendance::where('user_id', $user->id)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('created_at', '>=', Carbon::now()->subHours(2))
                ->orderByDesc('updated_at')
                ->first();

            if ($lastAttendance && !empty($lastAttendance->latitude) && !empty($lastAttendance->longitude)) {
                $prevLocation = new LatLong(doubleval($lastAttendance->latitude), doubleval($lastAttendance->longitude));
                $currentLocation = new LatLong($lat, $lng);
                $distanceMeters = $this->calculateDistance($currentLocation, $prevLocation);
                $elapsedSeconds = Carbon::now()->diffInSeconds(Carbon::parse($lastAttendance->updated_at));

                if ($elapsedSeconds > 0 && $elapsedSeconds < 1800) { // Within 30 minutes
                    $speedKmh = ($distanceMeters / 1000) / ($elapsedSeconds / 3600);
                    if ($speedKmh > 250) { // Speed faster than 250 km/h
                        return __('Absen Gagal: Terdeteksi perpindahan posisi lokasi yang tidak wajar / terlalu cepat (Indikasi Fake GPS).');
                    }
                }
            }
        }

        return null;
    }

    public function manualCheckIn()
    {
        if (is_null($this->currentLiveCoords) || count($this->currentLiveCoords) < 2) {
            $this->dangerBanner(__('Lokasi GPS belum terdeteksi. Harap izinkan akses lokasi (GPS) pada browser Anda terlebih dahulu.'));
            return;
        }

        $userLat = doubleval($this->currentLiveCoords[0]);
        $userLng = doubleval($this->currentLiveCoords[1]);

        if ($gpsError = $this->validateGpsHardening($userLat, $userLng)) {
            $this->dangerBanner($gpsError);
            return;
        }

        if (is_null($this->shift_id)) {
            $this->dangerBanner(__('Pilih shift terlebih dahulu sebelum melakukan absen.'));
            return;
        }

        $userLocation = new LatLong($userLat, $userLng);
        $barcodes = Barcode::all();

        if ($barcodes->isEmpty()) {
            $this->dangerBanner(__('Data barcode lokasi kantor belum terdaftar di sistem.'));
            return;
        }

        $matchedBarcode = null;
        $matchedMinDistance = null;
        $closestBarcode = null;
        $minDistance = null;

        foreach ($barcodes as $barcode) {
            $bCoords = $barcode->latLng;
            if (!$bCoords || !isset($bCoords['lat']) || !isset($bCoords['lng'])) continue;

            $barcodeLocation = new LatLong($bCoords['lat'], $bCoords['lng']);
            $distance = $this->calculateDistance($userLocation, $barcodeLocation);

            if (is_null($minDistance) || $distance < $minDistance) {
                $minDistance = $distance;
                $closestBarcode = $barcode;
            }

            if ($distance <= $barcode->radius) {
                if (is_null($matchedMinDistance) || $distance < $matchedMinDistance) {
                    $matchedMinDistance = $distance;
                    $matchedBarcode = $barcode;
                }
            }
        }

        if (!$matchedBarcode) {
            $maxRadius = $closestBarcode ? $closestBarcode->radius : 50;
            $distFormatted = number_format($minDistance, 0, ',', '.');
            $this->dangerBanner("Absen Masuk gagal: Anda berada di luar radius area kantor/barcode. Jarak Anda saat ini: {$distFormatted} meter (Batas radius: {$maxRadius} meter).");
            return;
        }

        $result = $this->scan($matchedBarcode->value);
        if ($result !== true && is_string($result)) {
            $this->dangerBanner($result);
        }
    }

    public function manualCheckOut()
    {
        if (is_null($this->currentLiveCoords) || count($this->currentLiveCoords) < 2) {
            $this->dangerBanner(__('Lokasi GPS belum terdeteksi. Harap izinkan akses lokasi (GPS) pada browser Anda terlebih dahulu.'));
            return;
        }

        $userLat = doubleval($this->currentLiveCoords[0]);
        $userLng = doubleval($this->currentLiveCoords[1]);

        if ($gpsError = $this->validateGpsHardening($userLat, $userLng)) {
            $this->dangerBanner($gpsError);
            return;
        }

        if (is_null($this->shift_id)) {
            $this->dangerBanner(__('Pilih shift terlebih dahulu sebelum melakukan absen.'));
            return;
        }

        $userLocation = new LatLong($userLat, $userLng);
        $barcodes = Barcode::all();

        if ($barcodes->isEmpty()) {
            $this->dangerBanner(__('Data barcode lokasi kantor belum terdaftar di sistem.'));
            return;
        }

        $matchedBarcode = null;
        $matchedMinDistance = null;
        $closestBarcode = null;
        $minDistance = null;

        foreach ($barcodes as $barcode) {
            $bCoords = $barcode->latLng;
            if (!$bCoords || !isset($bCoords['lat']) || !isset($bCoords['lng'])) continue;

            $barcodeLocation = new LatLong($bCoords['lat'], $bCoords['lng']);
            $distance = $this->calculateDistance($userLocation, $barcodeLocation);

            if (is_null($minDistance) || $distance < $minDistance) {
                $minDistance = $distance;
                $closestBarcode = $barcode;
            }

            if ($distance <= $barcode->radius) {
                if (is_null($matchedMinDistance) || $distance < $matchedMinDistance) {
                    $matchedMinDistance = $distance;
                    $matchedBarcode = $barcode;
                }
            }
        }

        if (!$matchedBarcode) {
            $maxRadius = $closestBarcode ? $closestBarcode->radius : 50;
            $distFormatted = number_format($minDistance, 0, ',', '.');
            $this->dangerBanner("Absen Keluar gagal: Anda berada di luar radius area kantor/barcode. Jarak Anda saat ini: {$distFormatted} meter (Batas radius: {$maxRadius} meter).");
            return;
        }

        $result = $this->scan($matchedBarcode->value);
        if ($result !== true && is_string($result)) {
            $this->dangerBanner($result);
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
        $shiftStartTime = $shift ? $shift->start_time : '08:00:00';
        $status = Carbon::now()->setTimeFromTimeString($shiftStartTime)->lt($now) ? 'late' : 'present';
        return Attendance::create([
            'user_id' => Auth::user()->id,
            'barcode_id' => $barcode->id,
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => null,
            'shift_id' => $shift?->id,
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
        // Only set isAbsence to true for formal approved full day leave / permits (sick, leave, permit, cuti, dayoff).
        // Note: 'absent' (Tidak Hadir) is NOT an approved leave and MUST allow employees to Check In / Check Out!
        $this->isAbsence = in_array($attendance->status, ['sick', 'leave', 'permit', 'cuti', 'dayoff']);
        if (is_null($this->currentLiveCoords) && !empty($attendance->latitude) && !empty($attendance->longitude)) {
            $this->currentLiveCoords = [doubleval($attendance->latitude), doubleval($attendance->longitude)];
        }
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

    public function updatedShiftId($value)
    {
        if (!empty($this->attendance?->time_in)) {
            $this->shift_id = $this->attendance->shift_id;
            $this->dangerBanner(__('Shift kerja tidak dapat diubah karena Anda sudah melakukan absen masuk hari ini.'));
        }
    }

    public function ensureShiftSelected()
    {
        $user = Auth::user();
        if (!$user) return;

        if (is_null($this->shifts)) {
            $this->shifts = Shift::forUser($user)->get();
        }

        if (is_null($this->shift_id) && $this->shifts->isNotEmpty()) {
            $divisionShifts = $this->shifts->filter(fn (Shift $s) => !is_null($s->division_id) && $s->division_id == $user->division_id);
            $candidateShifts = $divisionShifts->isNotEmpty() ? $divisionShifts : $this->shifts;

            $validTimes = array_filter($candidateShifts->pluck('start_time')->toArray());

            if (!empty($validTimes)) {
                $closest = ExtendedCarbon::now()->closestFromDateArray($validTimes);
                $matchedShift = $closest ? $candidateShifts->where(fn (Shift $shift) => $shift->start_time == $closest->format('H:i:s'))->first() : null;
                $this->shift_id = $matchedShift?->id ?? $candidateShifts->first()?->id;
            } else {
                $this->shift_id = $candidateShifts->first()?->id;
            }
        }
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
            $this->ensureShiftSelected();
        }
    }

    private ?float $memoizedDeduction = null;

    public function getRealtimeDeduction(): float
    {
        if ($this->memoizedDeduction !== null) {
            return $this->memoizedDeduction;
        }

        $user = Auth::user();
        if (!$user) return 0;

        $cacheKey = 'realtime_deduction_' . $user->id . '_' . date('Y-m-d');

        return $this->memoizedDeduction = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($user) {
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
            $daily_rate_approx = ($days_divisor > 0) ? ($fixed_income / $days_divisor) : 0;

            $total_absent = $missing_absent_days;

            $total_late_minutes = 0;
            $total_sick = 0;
            $total_excused = 0;
            $total_wfh = 0;

            foreach ($attendances as $att) {
                if ($att->status == 'late' && $att->shift) {
                    $time_in = Carbon::parse($att->time_in);
                    $attDateStr = $att->date instanceof Carbon ? $att->date->format('Y-m-d') : substr((string)$att->date, 0, 10);
                    $shift_start = Carbon::parse($attDateStr . ' ' . $att->shift->start_time);
                    if ($time_in->gt($shift_start)) {
                        $total_late_minutes += $time_in->diffInMinutes($shift_start);
                    }
                }
                if ($att->status == 'sick') $total_sick++;
                if ($att->status == 'permit') $total_excused++;
                if ($att->status == 'wfh') $total_wfh++;
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
        });
    }

    public function render()
    {
        $this->ensureShiftSelected();

        return view('livewire.scan', [
            'attendance' => $this->attendance,
            'shift_id' => $this->shift_id,
            'shifts' => $this->shifts ?: Shift::forUser(Auth::user())->get(),
            'currentLiveCoords' => $this->currentLiveCoords,
            'successMsg' => $this->successMsg,
            'isAbsence' => $this->isAbsence,
            'showMotivationModal' => $this->showMotivationModal,
            'showLocationMapModal' => $this->showLocationMapModal,
            'realtimeDeduction' => $this->getRealtimeDeduction(),
            'errors' => session('errors', new \Illuminate\Support\ViewErrorBag),
        ]);
    }
}
