<?php

namespace Tests\Feature;

use App\Livewire\ScanComponent;
use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\Division;
use App\Models\EmployeeMonthlyStat;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftCheckInGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_shifts_take_strict_priority_over_global_shifts()
    {
        $division = Division::create(['name' => 'IT & Software']);

        $globalShift = Shift::create([
            'name' => 'Global Morning',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'division_id' => null,
        ]);

        $divisionShift = Shift::create([
            'name' => 'IT Division Shift',
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
            'division_id' => $division->id,
        ]);

        $user = User::factory()->create([
            'name' => 'Zaenal IT',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $division->id,
        ]);

        $candidates = Shift::getCandidateShiftsForUser($user);

        // Must only contain division shifts
        $this->assertTrue($candidates->contains('id', $divisionShift->id));
        $this->assertFalse($candidates->contains('id', $globalShift->id));
    }

    public function test_user_without_division_shifts_falls_back_to_global_shifts()
    {
        $globalShift = Shift::create([
            'name' => 'Global Morning',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'division_id' => null,
        ]);

        $user = User::factory()->create([
            'name' => 'General Employee',
            'group' => 'user',
            'status' => 'active',
            'division_id' => null,
        ]);

        $candidates = Shift::getCandidateShiftsForUser($user);

        $this->assertTrue($candidates->contains('id', $globalShift->id));
    }

    public function test_check_in_window_is_open_early_more_than_two_hours_between_06_and_17()
    {
        $shift = Shift::create([
            'name' => 'Afternoon Shift',
            'start_time' => '13:00:00',
            'end_time' => '21:00:00',
        ]);

        // Simulated time: 08:00 WIB (5 hours before 13:00, within 06:00 - 17:00 daytime window)
        $simulatedNow = Carbon::today()->setTime(8, 0, 0);
        $window = $shift->getCheckInWindowInfo($simulatedNow);

        $this->assertTrue($window['is_open']);
        $this->assertEquals('06:00', $window['earliest_time_str']);
    }

    public function test_check_in_window_is_closed_if_before_06_and_more_than_two_hours_early()
    {
        $shift = Shift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        // Simulated time: 05:30 WIB (less than 06:00 and 2.5 hours before 08:00)
        $simulatedNow = Carbon::today()->setTime(5, 30, 0);
        $window = $shift->getCheckInWindowInfo($simulatedNow);

        $this->assertFalse($window['is_open']);
        $this->assertEquals('06:00', $window['earliest_time_str']);
    }

    public function test_check_in_window_for_night_shift_after_17_is_strictly_max_two_hours()
    {
        $shift = Shift::create([
            'name' => 'Night Shift',
            'start_time' => '21:00:00',
            'end_time' => '05:00:00',
        ]);

        // Simulated time: 18:00 WIB (3 hours before 21:00)
        $simulatedClosed = Carbon::today()->setTime(18, 0, 0);
        $windowClosed = $shift->getCheckInWindowInfo($simulatedClosed);
        $this->assertFalse($windowClosed['is_open']);
        $this->assertEquals('19:00', $windowClosed['earliest_time_str']);

        // Simulated time: 19:30 WIB (1.5 hours before 21:00)
        $simulatedOpen = Carbon::today()->setTime(19, 30, 0);
        $windowOpen = $shift->getCheckInWindowInfo($simulatedOpen);
        $this->assertTrue($windowOpen['is_open']);
    }

    public function test_check_in_window_is_open_within_two_hours_before_shift()
    {
        $shift = Shift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        // Simulated time: 06:30 WIB (1.5 hours before 08:00)
        $simulatedNow = Carbon::today()->setTime(6, 30, 0);
        $window = $shift->getCheckInWindowInfo($simulatedNow);

        $this->assertTrue($window['is_open']);
    }

    public function test_check_in_scan_is_rejected_when_shift_window_is_closed()
    {
        $user = User::factory()->create([
            'name' => 'Zaenal Employee',
            'group' => 'user',
            'status' => 'active',
        ]);

        $barcode = Barcode::create([
            'name' => 'Office Main',
            'value' => 'TEST_BC_GUARD',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 1000,
        ]);

        // Create shift far in future (e.g. 5 hours from now)
        $shift = Shift::create([
            'name' => 'Late Afternoon Shift',
            'start_time' => Carbon::now()->addHours(5)->format('H:i:s'),
            'end_time' => Carbon::now()->addHours(12)->format('H:i:s'),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ScanComponent::class)
            ->set('currentLiveCoords', [-6.200000, 106.816666])
            ->set('shift_id', $shift->id);

        $result = $component->instance()->scan('TEST_BC_GUARD', 'check_in');

        $this->assertStringContainsString('Absen Masuk ditolak', $result);
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
        ]);
    }

    public function test_leaderboard_caps_daily_early_arrival_minutes_at_120()
    {
        $user = User::factory()->create([
            'name' => 'Super Early User',
            'group' => 'user',
            'status' => 'active',
        ]);

        $shift = Shift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        // Create attendance checked in at 05:00:00 (3 hours = 180 mins early)
        $today = Carbon::today()->format('Y-m-d');
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => '05:00:00',
            'time_out' => '17:00:00',
            'shift_id' => $shift->id,
            'status' => 'present',
        ]);

        $period = Carbon::today()->format('Y-m');
        EmployeeMonthlyStat::recalculateForPeriod($period);

        $stat = EmployeeMonthlyStat::where('user_id', $user->id)
            ->where('period', $period)
            ->first();

        $this->assertNotNull($stat);
        // Even though 180 mins early, capped at max 120 mins
        $this->assertEquals(120, $stat->total_early_minutes);
    }

    public function test_shift_can_be_adjusted_before_check_out()
    {
        $user = User::factory()->create([
            'name' => 'Zaenal Shift Change',
            'group' => 'user',
            'status' => 'active',
        ]);

        $shift1 = Shift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $shift2 = Shift::create([
            'name' => 'Middle Shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'time_in' => '08:05:00',
            'shift_id' => $shift1->id,
            'status' => 'present',
        ]);

        $this->actingAs($user);

        Livewire::test(ScanComponent::class)
            ->set('shift_id', $shift2->id);

        $this->assertEquals($shift2->id, $attendance->fresh()->shift_id);
    }
}

