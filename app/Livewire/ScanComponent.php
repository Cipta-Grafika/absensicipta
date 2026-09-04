<?php

namespace App\Livewire;

use App\ExtendedCarbon;
use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;
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
    public ?string $geoSignature = null;
    public ?int $geoTimestamp = null;
    public ?float $geoAccuracy = null;
    public string $successMsg = '';
    public bool $isAbsence = false;
    public bool $showMotivationModal = false;
    public bool $showLocationMapModal = false;
    public bool $showDeductionDetailModal = false;
    public array $userDeductionDetails = [];
    public float $userTotalDeduction = 0.0;
    public string $deductionPeriod = '';
    public bool $isPayrollFinal = false;
    public string $motivationTitle = '';
    public string $motivationMessage = '';
    public string $motivationType = '';

    public static array $lockedStatuses = [
        'excused', 'permit', 'izin',
        'wfh',
        'sick', 'sakit',
        'leave', 'cuti',
        'special-leaves', 'special_leave', 'special-leave', 'cuti_khusus',
        'imp',
        'dayoff', 'off', 'libur'
    ];

    public function dangerBanner(string $message, ?string $title = null): void
    {
        $cleanMessage = preg_replace('/^(Absen Gagal|Absen Masuk gagal|Absen Keluar gagal|Presensi Gagal|Gagal)\s*:\s*/i', '', $message);

        $this->dispatch('alert-modal', [
            'type' => 'danger',
            'title' => $title ?? 'Absen Gagal',
            'message' => $cleanMessage,
            'buttonText' => 'Mengerti'
        ]);
        session()->flash('flash.banner', $cleanMessage);
        session()->flash('flash.bannerStyle', 'danger');
    }

    public function banner(string $message, string $style = 'success', ?string $title = null): void
    {
        $this->dispatch('alert-modal', [
            'type' => $style,
            'title' => $title ?? ($style === 'success' ? 'Berhasil' : ($style === 'warning' ? 'Peringatan' : 'Informasi')),
            'message' => $message,
            'buttonText' => $style === 'success' ? 'Siap, Lanjutkan!' : 'Mengerti'
        ]);
        session()->flash('flash.banner', $message);
        session()->flash('flash.bannerStyle', $style);
    }

    protected function getAppSecretKey(): string
    {
        return config('app.key') ?: sha1(config('app.name', 'AbsensiCiptaSecretFallbackKey'));
    }

    public function updateLiveLocation(float $lat, float $lng, float $accuracy, int $timestamp): ?string
    {
        if (!Auth::check()) {
            return __('Absen Gagal: Sesi pengguna tidak valid.');
        }

        $throttleKey = 'scan_location_update_' . Auth::id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 30)) {
            return __('Absen Gagal: Terlalu banyak permintaan perbaruan lokasi. Harap tunggu sebentar.');
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return __('Absen Gagal: Format koordinat GPS tidak valid.');
        }

        if ($accuracy <= 0 || $accuracy > 500) {
            return __('Absen Gagal: Akurasi sinyal GPS terdeteksi tidak wajar (' . round($accuracy) . 'm). Harap gunakan lokasi GPS asli.');
        }

        $now = time();
        if (abs($now - $timestamp) > 120) {
            return __('Absen Gagal: Waktu lokasi GPS tidak sesuai atau kadaluarsa.');
        }

        if ($gpsError = $this->validateGpsHardening($lat, $lng)) {
            return $gpsError;
        }

        $this->currentLiveCoords = [doubleval($lat), doubleval($lng)];
        $this->geoTimestamp = $timestamp;
        $this->geoAccuracy = $accuracy;
        $this->geoSignature = hash_hmac(
            'sha256',
            sprintf("%.6f|%.6f|%d|%s|%s", $lat, $lng, $timestamp, Auth::id(), session()->getId()),
            $this->getAppSecretKey()
        );

        return null;
    }

    protected function verifyLocationIntegrity(): ?string
    {
        if (app()->environment('testing') && is_null($this->geoSignature)) {
            return null;
        }

        if (!Auth::check()) {
            return __('Absen Gagal: Sesi otentikasi tidak valid.');
        }

        if (is_null($this->currentLiveCoords) || count($this->currentLiveCoords) < 2) {
            return __('Absen Gagal: Lokasi GPS belum terdeteksi. Harap izinkan akses lokasi (GPS) pada browser Anda terlebih dahulu.');
        }

        if (is_null($this->geoSignature) || is_null($this->geoTimestamp)) {
            return __('Absen Gagal: Tanda tangan keamanan lokasi GPS belum terverifikasi secara sah. Harap perbarui lokasi GPS.');
        }

        if (abs(time() - $this->geoTimestamp) > 300) {
            return __('Absen Gagal: Data lokasi GPS sudah kadaluarsa (lebih dari 5 menit). Harap perbarui posisi GPS Anda.');
        }

        $expectedSignature = hash_hmac(
            'sha256',
            sprintf("%.6f|%.6f|%d|%s|%s", doubleval($this->currentLiveCoords[0]), doubleval($this->currentLiveCoords[1]), $this->geoTimestamp, Auth::id(), session()->getId()),
            $this->getAppSecretKey()
        );

        if (!hash_equals($expectedSignature, $this->geoSignature)) {
            return __('Absen Gagal: Integritas tanda tangan lokasi GPS tidak valid (Terdeteksi manipulasi state Livewire). Harap perbarui posisi GPS Anda.');
        }

        return null;
    }

    public function scan(string $barcode, string $action = 'auto')
    {
        $lockKey = 'scan_lock_' . Auth::id() . '_' . date('Y-m-d');
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 5);

        if (!$lock->get()) {
            return __('Absen Gagal: Proses presensi sedang berjalan. Harap tunggu sebentar.');
        }

        try {
            return $this->executeScan($barcode, $action);
        } finally {
            $lock->release();
        }
    }

    protected function executeScan(string $barcode, string $action = 'auto')
    {
        if ($this->isAbsence || ($this->attendance && in_array(strtolower(trim((string)$this->attendance->status)), self::$lockedStatuses))) {
            return __('Absen Gagal: Presensi terkunci karena status Anda hari ini terdaftar sebagai ' . strtoupper($this->attendance?->status ?? 'IZIN/CUTI/SAKIT/WFH') . '.');
        }

        if ($integrityError = $this->verifyLocationIntegrity()) {
            return $integrityError;
        }

        if (is_null($this->shift_id)) {
            return __('Pilih shift terlebih dahulu sebelum melakukan absen.');
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

        return DB::transaction(function () use ($barcodeModel, $action) {
            /** @var Attendance */
            $existingAttendance = Attendance::where('user_id', Auth::user()->id)
                ->where('date', date('Y-m-d'))
                ->lockForUpdate()
                ->first();

            // Determine if action is check-in or check-out based on explicit intent
            if ($action === 'check_in') {
                if ($existingAttendance && !empty($existingAttendance->time_in)) {
                    $formattedTime = Carbon::parse($existingAttendance->time_in)->format('H:i:s');
                    return "Absen Masuk ditolak: Anda sudah melakukan Absen Masuk hari ini pada pukul {$formattedTime}.";
                }
                $isCheckInAction = true;
            } elseif ($action === 'check_out') {
                if (!$existingAttendance || empty($existingAttendance->time_in)) {
                    return "Absen Keluar ditolak: Anda belum melakukan Absen Masuk hari ini.";
                }
                if (!empty($existingAttendance->time_out)) {
                    $formattedTime = Carbon::parse($existingAttendance->time_out)->format('H:i:s');
                    return "Absen Keluar ditolak: Anda sudah melakukan Absen Keluar hari ini pada pukul {$formattedTime}.";
                }

                // Check Window Info for Check-Out
                $windowInfo = $this->checkOutWindowInfo();
                if (!$windowInfo['isOpen']) {
                    return "Absen Keluar gagal: Absen Keluar belum dibuka. Anda baru dapat melakukan Absen Keluar mulai pukul {$windowInfo['unlockTime']} (1 jam sebelum shift berakhir).";
                }

                // Anti fast double-tap / minimum cooldown (5 minutes)
                $timeInCarbon = Carbon::parse($existingAttendance->time_in);
                if (abs(Carbon::now()->diffInMinutes($timeInCarbon)) < 5) {
                    return "Absen Keluar ditolak: Anda baru saja melakukan Absen Masuk. Mohon tunggu setidaknya 5 menit sebelum melakukan Absen Keluar.";
                }

                $isCheckInAction = false;
            } else {
                // Auto action
                if (!$existingAttendance || empty($existingAttendance->time_in)) {
                    $isCheckInAction = true;
                } else {
                    if (!empty($existingAttendance->time_out)) {
                        return "Presensi hari ini sudah lengkap (Masuk & Keluar).";
                    }

                    // Check Window Info for Auto Check-Out
                    $windowInfo = $this->checkOutWindowInfo();
                    if (!$windowInfo['isOpen']) {
                        return "Absen Keluar gagal: Absen Keluar belum dibuka. Anda baru dapat melakukan Absen Keluar mulai pukul {$windowInfo['unlockTime']} (1 jam sebelum shift berakhir).";
                    }

                    // Anti fast double-tap / minimum cooldown (5 minutes)
                    $timeInCarbon = Carbon::parse($existingAttendance->time_in);
                    if (abs(Carbon::now()->diffInMinutes($timeInCarbon)) < 5) {
                        return "Absen Keluar ditolak: Anda baru saja melakukan Absen Masuk. Mohon tunggu setidaknya 5 menit sebelum melakukan Absen Keluar.";
                    }

                    $isCheckInAction = false;
                }
            }

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
                $this->dispatch('alert-modal', [
                    'type' => $feedback['type'],
                    'title' => $feedback['title'],
                    'message' => $feedback['message'],
                    'icon' => $feedback['icon'] ?? null,
                    'badge_color' => $feedback['badge_color'] ?? null,
                    'buttonText' => 'Siap, Lanjutkan!'
                ]);
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
                $this->dispatch('alert-modal', [
                    'type' => $feedback['type'],
                    'title' => $feedback['title'],
                    'message' => $feedback['message'],
                    'icon' => $feedback['icon'] ?? null,
                    'badge_color' => $feedback['badge_color'] ?? null,
                    'buttonText' => 'Siap, Lanjutkan!'
                ]);
            }

            if ($attendance) {
                $this->setAttendance($attendance->fresh());
                Attendance::clearUserAttendanceCache(Auth::user(), Carbon::parse($attendance->date));
                return true;
            }

            return true;
        });
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
        $lockKey = 'scan_lock_' . Auth::id() . '_' . date('Y-m-d');
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 5);
        if (!$lock->get()) {
            $this->dangerBanner(__('Absen Masuk gagal: Presensi sedang diproses. Harap tunggu sebentar.'));
            return;
        }

        try {
            if ($this->isAbsence) {
                $this->dangerBanner(__('Absen Masuk gagal: Operasi tidak dapat dilakukan karena status Anda hari ini terdaftar sebagai ' . strtoupper($this->attendance?->status ?? 'IZIN/CUTI/SAKIT/WFH') . '.'));
                return;
            }

            // Strict Idempotency Check: Prevent duplicate check-in
            $existingAttendance = Attendance::where('user_id', Auth::id())
                ->where('date', date('Y-m-d'))
                ->first();

            if ($existingAttendance && !empty($existingAttendance->time_in)) {
                $formattedTime = Carbon::parse($existingAttendance->time_in)->format('H:i:s');
                $this->dangerBanner("Absen Masuk ditolak: Anda sudah melakukan Absen Masuk hari ini pada pukul {$formattedTime}.");
                return;
            }

            if ($integrityError = $this->verifyLocationIntegrity()) {
                $this->dangerBanner($integrityError);
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

            $result = $this->executeScan($matchedBarcode->value, 'check_in');
            if ($result !== true && is_string($result)) {
                $this->dangerBanner($result);
            }
        } finally {
            $lock->release();
        }
    }

    #[\Livewire\Attributes\Computed]
    public function checkOutWindowInfo(): array
    {
        $shiftId = $this->shift_id ?? $this->attendance?->shift_id;
        $shift = $shiftId ? Shift::find($shiftId) : null;

        if (!$shift) {
            return [
                'hasShift' => false,
                'isOpen' => false,
                'unlockTime' => '-',
                'shiftEndTime' => '-',
            ];
        }

        $shiftEndTimeStr = $shift->end_time ?? '17:00:00';
        $shiftStartTimeStr = $shift->start_time ?? '08:00:00';

        $endTime = Carbon::today()->setTimeFromTimeString($shiftEndTimeStr);
        if (Carbon::parse($shiftEndTimeStr)->lt(Carbon::parse($shiftStartTimeStr))) {
            if (Carbon::now()->lt(Carbon::today()->setTimeFromTimeString($shiftStartTimeStr))) {
                $endTime = Carbon::today()->setTimeFromTimeString($shiftEndTimeStr);
            } else {
                $endTime = Carbon::tomorrow()->setTimeFromTimeString($shiftEndTimeStr);
            }
        }

        $allowCheckOutFrom = $endTime->copy()->subHour();
        $isOpen = Carbon::now()->gte($allowCheckOutFrom);

        return [
            'hasShift' => true,
            'isOpen' => $isOpen,
            'unlockTime' => $allowCheckOutFrom->format('H:i'),
            'shiftEndTime' => $endTime->format('H:i'),
        ];
    }

    public function manualCheckOut()
    {
        $lockKey = 'scan_lock_' . Auth::id() . '_' . date('Y-m-d');
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 5);
        if (!$lock->get()) {
            $this->dangerBanner(__('Absen Keluar gagal: Presensi sedang diproses. Harap tunggu sebentar.'));
            return;
        }

        try {
            if ($this->isAbsence) {
                $this->dangerBanner(__('Absen Keluar gagal: Operasi tidak dapat dilakukan karena status Anda hari ini terdaftar sebagai ' . strtoupper($this->attendance?->status ?? 'IZIN/CUTI/SAKIT/WFH') . '.'));
                return;
            }

            // Strict Pre-validation for Check-Out
            $existingAttendance = Attendance::where('user_id', Auth::id())
                ->where('date', date('Y-m-d'))
                ->first();

            if (!$existingAttendance || empty($existingAttendance->time_in)) {
                $this->dangerBanner("Absen Keluar ditolak: Anda belum melakukan Absen Masuk hari ini.");
                return;
            }

            if (!empty($existingAttendance->time_out)) {
                $formattedTime = Carbon::parse($existingAttendance->time_out)->format('H:i:s');
                $this->dangerBanner("Absen Keluar ditolak: Anda sudah melakukan Absen Keluar hari ini pada pukul {$formattedTime}.");
                return;
            }

            if (is_null($this->shift_id)) {
                $this->shift_id = $this->attendance?->shift_id;
            }

            if (is_null($this->shift_id)) {
                $this->dangerBanner(__('Pilih shift terlebih dahulu sebelum melakukan absen.'));
                return;
            }

            $windowInfo = $this->checkOutWindowInfo();
            if (!$windowInfo['hasShift']) {
                $this->dangerBanner(__('Absen Keluar gagal: Pilih shift terlebih dahulu sebelum melakukan absen.'));
                return;
            }

            if (!$windowInfo['isOpen']) {
                $this->dangerBanner("Absen Keluar gagal: Absen Keluar belum dibuka. Anda baru dapat melakukan Absen Keluar mulai pukul {$windowInfo['unlockTime']} (1 jam sebelum shift berakhir).");
                return;
            }

            if ($integrityError = $this->verifyLocationIntegrity()) {
                $this->dangerBanner($integrityError);
                return;
            }

            $userLat = doubleval($this->currentLiveCoords[0]);
            $userLng = doubleval($this->currentLiveCoords[1]);

            if ($gpsError = $this->validateGpsHardening($userLat, $userLng)) {
                $this->dangerBanner($gpsError);
                return;
            }

            $userLocation = new LatLong($userLat, $userLng);

            // Strict Check-In Barcode Lock Enforcement:
            // If user already checked in at a specific barcode, verify radius strictly against THAT barcode!
            $checkInBarcode = null;
            if ($existingAttendance->barcode_id) {
                $checkInBarcode = Barcode::find($existingAttendance->barcode_id);
            }

            if ($checkInBarcode && isset($checkInBarcode->latLng['lat'], $checkInBarcode->latLng['lng'])) {
                $barcodeLocation = new LatLong($checkInBarcode->latLng['lat'], $checkInBarcode->latLng['lng']);
                $distance = $this->calculateDistance($userLocation, $barcodeLocation);

                if ($distance > $checkInBarcode->radius) {
                    $distFormatted = number_format($distance, 0, ',', '.');
                    $this->dangerBanner("Absen Keluar gagal: Anda berada di luar radius lokasi kantor tempat Absen Masuk ({$checkInBarcode->name}). Jarak Anda saat ini: {$distFormatted} meter (Batas radius: {$checkInBarcode->radius} meter).");
                    return;
                }

                $matchedBarcode = $checkInBarcode;
            } else {
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
            }

            $result = $this->executeScan($matchedBarcode->value, 'check_out');
            if ($result !== true && is_string($result)) {
                $this->dangerBanner($result);
            }
        } finally {
            $lock->release();
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
        $statusLower = strtolower(trim((string)$attendance->status));
        $this->isAbsence = in_array($statusLower, self::$lockedStatuses);

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
        if ($this->isAbsence) {
            $this->shift_id = $this->attendance?->shift_id;
            $this->dangerBanner(__('Shift kerja terkunci karena Anda tercatat berstatus ' . strtoupper($this->attendance?->status ?? 'IZIN/CUTI/SAKIT/WFH') . ' pada hari ini.'));
            return;
        }

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

    public function calculateRealtimeDeductionData($user): array
    {
        $salary = $user?->salary;
        $startPeriod = Carbon::now()->startOfMonth();
        $today = Carbon::today();
        $periodMonth = date('Y-m');
        $periodName = $startPeriod->translatedFormat('F Y');

        if (!$salary) {
            return [
                'total' => 0.0,
                'period' => $periodName,
                'is_payroll_final' => false,
                'items' => [],
            ];
        }

        // 1. SINGLE SOURCE OF TRUTH: If official Payroll for current month already exists and has details, use its snapshot directly!
        $currentMonthPayroll = \App\Models\Payroll::with('details')
            ->where('employee_id', $user->id)
            ->where('period_month', $periodMonth)
            ->first();

        if ($currentMonthPayroll && $currentMonthPayroll->details->isNotEmpty()) {
            $items = [];
            foreach ($currentMonthPayroll->details->where('type', 'deduction') as $pDetail) {
                $nameLower = strtolower($pDetail->name);
                $detailSubtitle = 'Potongan Payroll Resmi';

                if (str_contains($nameLower, 'izin') || str_contains($nameLower, 'sakit') || str_contains($nameLower, 'cuti') || str_contains($nameLower, 'wfh') || str_contains($nameLower, 'alpa') || str_contains($nameLower, 'terlambat') || str_contains($nameLower, 'imp')) {
                    $detailSubtitle = 'Potongan Kehadiran / Presensi';
                } elseif (str_contains($nameLower, 'syirkah')) {
                    $detailSubtitle = 'Simpanan Syirkah';
                } elseif (str_contains($nameLower, 'pinjaman') || str_contains($nameLower, 'kasbon')) {
                    $detailSubtitle = 'Cicilan Pinjaman';
                } elseif (str_contains($nameLower, 'bpjs') || str_contains($nameLower, 'pph')) {
                    $detailSubtitle = 'Pajak & Jaminan Sosial';
                } elseif (str_contains($nameLower, 'error')) {
                    $detailSubtitle = 'Potongan Kerusakan / Error Produksi';
                } elseif (str_contains($nameLower, 'fleksibel') || str_contains($nameLower, 'program')) {
                    $detailSubtitle = 'Potongan Program Khusus';
                }

                $items[] = [
                    'name' => $pDetail->name,
                    'detail' => $detailSubtitle,
                    'amount' => (float) $pDetail->amount,
                ];
            }

            $totalDeduction = (float) $currentMonthPayroll->total_deduction;
            if ($totalDeduction <= 0 && !empty($items)) {
                $totalDeduction = (float) array_sum(array_column($items, 'amount'));
            }

            return [
                'total' => round($totalDeduction, 0),
                'period' => $periodName,
                'is_payroll_final' => true,
                'payroll_status' => $currentMonthPayroll->status,
                'items' => $items,
            ];
        }

        // 2. REALTIME ESTIMATION: When Payroll has not yet been generated for current month
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
        $todayDate = Carbon::today()->startOfDay();

        for ($d = $startPeriod->copy(); $d->lte($today); $d->addDay()) {
            $isPastDate = $d->startOfDay()->lt($todayDate);
            if ($scheduleContext->isWorkingDay($user, $d)) {
                $records = $attendancesByDate->get($d->format('Y-m-d'), collect());
                $hasValidRecord = $records->whereNotIn('status', ['absent', 'dayoff'])->isNotEmpty();
                $isExplicitDayOff = $records->where('status', 'dayoff')->isNotEmpty();

                if ($isPastDate && !$hasValidRecord && !$isExplicitDayOff) {
                    $missing_absent_days++;
                }

                if ($records->whereIn('status', ['leave', 'special-leaves'])->isNotEmpty()) {
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

        $days_divisor = (int) ($salary->working_days_per_month ?: 25);
        $fixed_income = (int) round($salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance);
        $daily_rate_approx = ($days_divisor > 0) ? ($fixed_income / $days_divisor) : 0;

        $total_absent = $missing_absent_days;
        $total_late_minutes = 0;
        $total_sick = 0;
        $total_excused = 0;
        $total_wfh = 0;

        foreach ($attendances as $att) {
            if ($att->status == 'late' && $att->shift && $att->time_in) {
                $attDateStr = $att->date instanceof Carbon ? $att->date->format('Y-m-d') : substr((string)$att->date, 0, 10);
                $time_in = Carbon::parse($attDateStr . ' ' . Carbon::parse($att->time_in)->format('H:i:s'));
                $shift_start = Carbon::parse($attDateStr . ' ' . Carbon::parse($att->shift->start_time)->format('H:i:s'));
                if ($time_in->gt($shift_start)) {
                    $total_late_minutes += (int) abs($time_in->diffInMinutes($shift_start));
                }
            }
            if (in_array($att->status, ['sick', 'sakit'])) $total_sick++;
            if (in_array($att->status, ['excused', 'permit', 'izin'])) $total_excused++;
            if (in_array($att->status, ['wfh'])) $total_wfh++;
        }

        // Unreplaced IMP Minutes
        $total_unreplaced_imp_minutes = 0;
        foreach ($attendances->where('status', 'imp') as $att) {
            $imp_duration = $att->imp_duration_minutes ?? 0;
            $replaced = $att->replaced_duration_minutes ?? 0;
            $unreplaced = max(0, $imp_duration - $replaced);
            $total_unreplaced_imp_minutes += $unreplaced;
        }

        $isCiptaFood = strcasecmp(trim($user->division?->name ?? ''), 'Cipta Food') === 0;
        if ($isCiptaFood) {
            $late_deduction = (int) ($late_days_count * 10000);
        } else {
            $late_rate = $salary->late_deduction_per_minute ?? $salary->late_deduction_rate ?? 0;
            $late_deduction = (int) round($total_late_minutes * $late_rate);
        }
        $imp_deduction = ($days_divisor > 0) ? (int) round($total_unreplaced_imp_minutes * ($fixed_income / ($days_divisor * 8 * 60))) : 0;

        $effective_absent = min($total_absent, $days_divisor);
        $absent_deduction = (int) round($daily_rate_approx * $effective_absent);

        $effective_excused = min($total_excused, $days_divisor);
        $excused_deduction = ($days_divisor > 0) ? (int) round(($effective_excused / ($days_divisor * 2)) * ($salary->basic_salary ?? 0) + ($effective_excused / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;

        $effective_sick = min($total_sick, $days_divisor);
        $sick_deduction = ($days_divisor > 0) ? (int) round(($effective_sick / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;

        $effective_cuti = min($penalized_cuti_days, $days_divisor);
        $cuti_deduction = ($days_divisor > 0) ? (int) round(($effective_cuti / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;

        $effective_wfh = min($total_wfh, $days_divisor);
        if ($user->count_wfo) {
            $wfh_deduction = 0;
        } else {
            $wfh_deduction = ($days_divisor > 0) ? (int) round(($effective_wfh / $days_divisor) * (0.5 * $fixed_income)) : 0;
        }

        $late_penalty_deduction = ($late_days_count > 3) ? (int) round(0.10 * $salary->attendance_allowance) : 0;

        if ($salary->salary_type == 'daily') {
            $absent_deduction = 0;
            $excused_deduction = 0;
            $sick_deduction = 0;
            $cuti_deduction = 0;
            $wfh_deduction = 0;
        }

        $items = [];
        if ($absent_deduction > 0) {
            $items[] = [
                'name' => 'Potongan Alpa (' . $total_absent . ' Hari)',
                'detail' => $total_absent . ' hari tidak masuk kerja',
                'amount' => (float) round($absent_deduction, 0),
            ];
        }
        if ($late_deduction > 0) {
            $items[] = [
                'name' => 'Potongan Keterlambatan',
                'detail' => $isCiptaFood ? ($late_days_count . ' hari (Flat Rp 10.000 / hari)') : ($total_late_minutes . ' menit'),
                'amount' => (float) round($late_deduction, 0),
            ];
        }
        if ($late_penalty_deduction > 0) {
            $items[] = [
                'name' => 'Penalti Keterlambatan (> 3 Hari)',
                'detail' => '10% dari Tunjangan Kehadiran (' . $late_days_count . ' hari terlambat)',
                'amount' => (float) round($late_penalty_deduction, 0),
            ];
        }
        if ($imp_deduction > 0) {
            $items[] = [
                'name' => 'Potongan IMP Belum Diganti',
                'detail' => $total_unreplaced_imp_minutes . ' menit',
                'amount' => (float) round($imp_deduction, 0),
            ];
        }
        if ($excused_deduction > 0) {
            $items[] = [
                'name' => 'Potongan Izin (' . $total_excused . ' Hari)',
                'detail' => $total_excused . ' hari izin resmi',
                'amount' => (float) round($excused_deduction, 0),
            ];
        }
        if ($sick_deduction > 0) {
            $items[] = [
                'name' => 'Potongan Sakit (' . $total_sick . ' Hari)',
                'detail' => $total_sick . ' hari surat sakit',
                'amount' => (float) round($sick_deduction, 0),
            ];
        }
        if ($cuti_deduction > 0) {
            $items[] = [
                'name' => 'Potongan Cuti Berturut-turut',
                'detail' => $penalized_cuti_days . ' hari (> 2 hari berturut-turut)',
                'amount' => (float) round($cuti_deduction, 0),
            ];
        }
        if ($wfh_deduction > 0) {
            $items[] = [
                'name' => 'Potongan WFH (' . $total_wfh . ' Hari)',
                'detail' => $total_wfh . ' hari WFH',
                'amount' => (float) round($wfh_deduction, 0),
            ];
        }

        // Syirkah / Savings
        if ($salary->savings_id && $salary->savings) {
            $savingProgram = $salary->savings;
            $syirkah_mandatory = (int) round($savingProgram->mandatory_savings);
            $syirkah_secondary = ($salary->custom_secondary_savings !== null)
                ? (int) round($salary->custom_secondary_savings)
                : (int) round($savingProgram->secondary_savings);
            $syirkah_total = $syirkah_mandatory + $syirkah_secondary;
            if ($syirkah_total > 0) {
                $items[] = [
                    'name' => 'Potongan Syirkah',
                    'detail' => 'Simpanan Wajib & Sukarela',
                    'amount' => (float) $syirkah_total,
                ];
            }
        }

        // Active Loans (Kasbon / Pinjaman)
        $activeLoans = \App\Models\Loan::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('remaining_balance', '>', 0)
            ->get();
        foreach ($activeLoans as $loan) {
            $inst = (int) round(min($loan->installment_amount, $loan->remaining_balance));
            if ($inst > 0) {
                $items[] = [
                    'name' => 'Cicilan Pinjaman',
                    'detail' => 'Kasbon / Pinjaman Karyawan',
                    'amount' => (float) $inst,
                ];
            }
        }

        // BPJS
        if (($salary->bpjs ?? 0) > 0) {
            $items[] = [
                'name' => 'Potongan BPJS',
                'detail' => 'Potongan Tetap Bulanan',
                'amount' => (float) $salary->bpjs,
            ];
        }

        // PPh 21
        if ($salary->taxMaster && $salary->taxMaster->rate_percentage > 0) {
            $approxGross = $fixed_income;
            $pphEst = (int) round(($salary->taxMaster->rate_percentage / 100) * $approxGross);
            if ($pphEst > 0) {
                $items[] = [
                    'name' => 'Potongan PPh 21 (' . $salary->taxMaster->formatted_rate . ')',
                    'detail' => 'Estimasi PPh 21 TER',
                    'amount' => (float) $pphEst,
                ];
            }
        } elseif (($salary->pph21 ?? 0) > 0) {
            $items[] = [
                'name' => 'Potongan PPh 21',
                'detail' => 'Potongan Tetap Bulanan',
                'amount' => (float) $salary->pph21,
            ];
        }

        // Flexible Deductions
        $flexDeds = \App\Models\FlexibleDeduction::where('user_id', $user->id)
            ->where('period_month', $periodMonth)
            ->where('deduction_source', 'payroll')
            ->with('program')
            ->get();
        foreach ($flexDeds as $fd) {
            $items[] = [
                'name' => 'Potongan: ' . ($fd->program->name ?? 'Fleksibel'),
                'detail' => 'Potongan Tambahan Payroll',
                'amount' => (float) $fd->amount,
            ];
        }

        // Error Deductions
        $activeErrorDeds = \App\Models\ErrorDeduction::where('user_id', $user->id)
            ->where('period_month', $periodMonth)
            ->where('deduction_source', 'payroll')
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        foreach ($activeErrorDeds as $ed) {
            $items[] = [
                'name' => 'Potongan Error: ' . $ed->error_title,
                'detail' => 'Kesalahan Produksi / Log Error',
                'amount' => (float) $ed->amount,
            ];
        }

        $total_deduction = (float) array_sum(array_column($items, 'amount'));

        return [
            'total' => round($total_deduction, 0),
            'period' => $periodName,
            'is_payroll_final' => false,
            'items' => $items,
        ];
    }

    public function getRealtimeDeduction(): float
    {
        if ($this->memoizedDeduction !== null) {
            return $this->memoizedDeduction;
        }

        $user = Auth::user();
        if (!$user) return 0;

        $cacheKey = 'realtime_deduction_' . $user->id . '_' . date('Y-m-d');

        return $this->memoizedDeduction = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($user) {
            $data = $this->calculateRealtimeDeductionData($user);
            return (float) $data['total'];
        });
    }

    public function openDeductionDetailModal()
    {
        $user = Auth::user();
        if (!$user) return;

        $breakdown = $this->calculateRealtimeDeductionData($user);
        $this->userDeductionDetails = $breakdown['items'];
        $this->userTotalDeduction = $breakdown['total'];
        $this->deductionPeriod = $breakdown['period'];
        $this->isPayrollFinal = $breakdown['is_payroll_final'] ?? false;
        $this->showDeductionDetailModal = true;
    }

    #[On('refresh-deduction-from-sse')]
    public function handleDeductionSSEUpdate($payload = null): void
    {
        $this->memoizedDeduction = null;
        $user = Auth::user();
        if ($user) {
            $cacheKey = 'realtime_deduction_' . $user->id . '_' . date('Y-m-d');
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            if ($this->showDeductionDetailModal) {
                $breakdown = $this->calculateRealtimeDeductionData($user);
                $this->userDeductionDetails = $breakdown['items'];
                $this->userTotalDeduction = $breakdown['total'];
                $this->deductionPeriod = $breakdown['period'];
                $this->isPayrollFinal = $breakdown['is_payroll_final'] ?? false;
            }
        }
    }

    public function closeDeductionDetailModal()
    {
        $this->showDeductionDetailModal = false;
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
