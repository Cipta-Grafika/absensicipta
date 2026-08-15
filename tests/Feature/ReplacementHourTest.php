<?php

use App\Models\User;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\ReplacementHour;
use App\Livewire\User\ReplacementHourComponent;
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

test('replacement hour component can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ReplacementHourComponent::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.user.replacement-hour-component');
});

test('handleDateClick opens submission modal for non-existing replacement date', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $dateStr = '2026-08-10';

    Livewire::test(ReplacementHourComponent::class)
        ->call('handleDateClick', $dateStr)
        ->assertSet('isDateModalOpen', true)
        ->assertSet('isDetailModalOpen', false)
        ->assertSet('replaced_date', $dateStr);
});

test('handleDateClick opens detail modal when replacement hour exists for selected date', function () {
    $user = User::factory()->create();
    $shift = Shift::create([
        'name' => 'Regular Shift Test',
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $dateStr = '2026-08-12';

    $replacement = ReplacementHour::create([
        'user_id' => $user->id,
        'replaced_date' => $dateStr,
        'replacement_date' => '2026-08-15',
        'start_hour' => '08:00',
        'end_hour' => '17:00',
        'shift_id' => $shift->id,
        'reason' => 'Substitusi jam IMP',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(ReplacementHourComponent::class)
        ->call('handleDateClick', $dateStr)
        ->assertSet('isDetailModalOpen', true)
        ->assertSet('isDateModalOpen', false)
        ->assertSet('selectedReplacement.id', $replacement->id);
});

test('user can submit replacement hour for an IMP attendance date', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $shift = Shift::create([
        'name' => 'General Shift',
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $impDate = '2026-08-14';

    // Create IMP attendance
    Attendance::create([
        'user_id' => $user->id,
        'date' => $impDate,
        'status' => 'imp',
        'check_in' => '08:00',
        'check_out' => '12:00',
    ]);

    $file = UploadedFile::fake()->image('proof.jpg');

    $this->actingAs($user);

    Livewire::test(ReplacementHourComponent::class)
        ->call('handleDateClick', $impDate)
        ->set('replacement_date', '2026-08-20')
        ->set('start_hour', '08:00')
        ->set('end_hour', '12:00')
        ->set('shift_id', $shift->id)
        ->set('reason', 'Ganti jam IMP tanggal 14 Agust')
        ->set('attachment', $file)
        ->call('submitDateModal')
        ->assertSet('isDateModalOpen', false)
        ->assertSet('modalError', null);

    $this->assertDatabaseHas('replacement_hours', [
        'user_id' => $user->id,
        'replaced_date' => $impDate,
        'replacement_date' => '2026-08-20',
        'start_hour' => '08:00',
        'end_hour' => '12:00',
        'shift_id' => $shift->id,
        'status' => 'pending',
    ]);
});
