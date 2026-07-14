<?php

namespace App\Livewire;

use App\ExtendedCarbon;
use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
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
        $this->shifts = Shift::all();

        /** @var Attendance */
        $attendance = Attendance::where('user_id', Auth::user()->id)
            ->where('date', date('Y-m-d'))->first();
        if ($attendance) {
            $this->setAttendance($attendance);
        } else {
            // get closest shift from current time
            $closest = ExtendedCarbon::now()
                ->closestFromDateArray($this->shifts->pluck('start_time')->toArray());

            $this->shift_id = $this->shifts
                ->where(fn (Shift $shift) => $shift->start_time == $closest->format('H:i:s'))
                ->first()->id;
        }
    }

    public function render()
    {
        return view('livewire.scan');
    }
}
