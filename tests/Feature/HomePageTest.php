<?php

namespace Tests\Feature;

use App\Livewire\ScanComponent;
use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\Division;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_successfully_for_user_not_checked_in()
    {
        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'division_id' => null,
        ]);

        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'group' => 'user',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertSeeLivewire(ScanComponent::class);
    }

    public function test_home_page_renders_successfully_for_user_checked_in_during_working_hours()
    {
        // Set time to 10:00:00 (Check-In done, Check-Out window locked)
        Carbon::setTestNow('2026-09-04 10:00:00');

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'division_id' => null,
        ]);

        $user = User::factory()->create([
            'name' => 'Ahmad Karyawan',
            'group' => 'user',
            'status' => 'active',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'time_in' => '07:55:00',
            'time_out' => null,
            'shift_id' => $shift->id,
            'status' => 'present',
        ]);

        // This was previously failing with 500 Undefined variable $hasShift
        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertSee('Jam Masuk');
        $response->assertSee('Jam Keluar');
        $response->assertSee('07:55:00');
        $response->assertSee('Buka Pukul 16:00');

        Livewire::actingAs($user)
            ->test(ScanComponent::class)
            ->assertStatus(200)
            ->assertSee('07:55:00')
            ->assertSee('Buka Pukul 16:00');
    }

    public function test_home_page_renders_for_user_checked_out()
    {
        Carbon::setTestNow('2026-09-04 17:05:00');

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'division_id' => null,
        ]);

        $user = User::factory()->create([
            'name' => 'Siti Karyawan',
            'group' => 'user',
            'status' => 'active',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'time_in' => '07:55:00',
            'time_out' => '17:02:00',
            'shift_id' => $shift->id,
            'status' => 'present',
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertSee('07:55:00');
        $response->assertSee('17:02:00');
        $response->assertSee('Sudah Keluar');
    }

    public function test_home_page_renders_for_user_with_locked_status()
    {
        $user = User::factory()->create([
            'name' => 'Izin Employee',
            'group' => 'user',
            'status' => 'active',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
            'time_in' => null,
            'time_out' => null,
            'status' => 'izin',
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertSee('Presensi Hari Ini Terkunci');
    }
}
