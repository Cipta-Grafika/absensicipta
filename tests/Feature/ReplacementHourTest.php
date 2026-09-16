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
        ->assertSet('isOptionsModalOpen', false)
        ->assertSet('isDetailModalOpen', false)
        ->assertSet('replaced_date', $dateStr);
});

test('handleDateClick opens options modal when replacement hour exists for selected date', function () {
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
        ->assertSet('isOptionsModalOpen', true)
        ->assertSet('isDetailModalOpen', false)
        ->assertSet('isDateModalOpen', false)
        ->assertSet('activeCalendarDate', $dateStr);
});

test('user can open detail modal from options modal and return back to options modal', function () {
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
        ->assertSet('isOptionsModalOpen', true)
        ->call('openDetailModal', $replacement->id)
        ->assertSet('isDetailModalOpen', true)
        ->assertSet('isOptionsModalOpen', false)
        ->assertSet('selectedReplacement.id', $replacement->id)
        ->call('backToOptionsModal')
        ->assertSet('isDetailModalOpen', false)
        ->assertSet('isOptionsModalOpen', true)
        ->assertSet('selectedReplacement', null);
});

test('user can open create modal from options modal when count is less than 5', function () {
    $user = User::factory()->create();
    $shift = Shift::create([
        'name' => 'Regular Shift Test',
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $dateStr = '2026-08-12';

    ReplacementHour::create([
        'user_id' => $user->id,
        'replaced_date' => $dateStr,
        'replacement_date' => '2026-08-15',
        'start_hour' => '08:00',
        'end_hour' => '17:00',
        'shift_id' => $shift->id,
        'reason' => 'Pengajuan 1',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(ReplacementHourComponent::class)
        ->call('handleDateClick', $dateStr)
        ->assertSet('isOptionsModalOpen', true)
        ->call('openCreateModal')
        ->assertSet('isDateModalOpen', true)
        ->assertSet('isOptionsModalOpen', false)
        ->assertSet('replaced_date', $dateStr);
});

test('user cannot create more than 5 replacement hours for the same replaced date', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $shift = Shift::create([
        'name' => 'General Shift',
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $impDate = '2026-08-14';

    Attendance::create([
        'user_id' => $user->id,
        'date' => $impDate,
        'status' => 'imp',
        'check_in' => '08:00',
        'check_out' => '12:00',
    ]);

    // Create 5 existing replacements for this user and date
    for ($i = 1; $i <= 5; $i++) {
        ReplacementHour::create([
            'user_id' => $user->id,
            'replaced_date' => $impDate,
            'replacement_date' => '2026-08-2' . $i,
            'start_hour' => '08:00',
            'end_hour' => '10:00',
            'shift_id' => $shift->id,
            'reason' => "Pengajuan ke-$i",
            'status' => 'pending',
        ]);
    }

    $this->actingAs($user);

    // Test openCreateModal blocked
    Livewire::test(ReplacementHourComponent::class)
        ->call('handleDateClick', $impDate)
        ->assertSet('isOptionsModalOpen', true)
        ->call('openCreateModal')
        ->assertSet('isDateModalOpen', false)
        ->assertNotSet('modalError', null);

    // Test submitDateModal blocked on backend
    $file = UploadedFile::fake()->image('proof.jpg');
    Livewire::test(ReplacementHourComponent::class)
        ->set('replaced_date', $impDate)
        ->set('replacement_date', '2026-08-26')
        ->set('start_hour', '08:00')
        ->set('end_hour', '10:00')
        ->set('shift_id', $shift->id)
        ->set('reason', 'Pengajuan ke-6')
        ->set('attachment', $file)
        ->call('submitDateModal')
        ->assertNotSet('modalError', null);

    expect(ReplacementHour::where('user_id', $user->id)->where('replaced_date', $impDate)->count())->toBe(5);
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
