<?php

use App\Models\User;
use App\Models\Overtime;
use App\Livewire\User\OvertimeComponent;
use Livewire\Livewire;
use Carbon\Carbon;

test('user can open edit modal and update pending overtime submission', function () {
    $user = User::factory()->create();

    $overtime = Overtime::create([
        'employee_id' => $user->id,
        'overtime_date' => '2026-09-20',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'break' => '0:30',
        'duration_hours' => 2.5,
        'reason' => 'Pekerjaan cetak brosur',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(OvertimeComponent::class)
        ->call('editOvertime', $overtime->id)
        ->assertSet('editingOvertimeId', $overtime->id)
        ->assertSet('isDateModalOpen', true)
        ->assertSet('start_time', '17:00')
        ->assertSet('end_time', '20:00')
        ->assertSet('break', '0:30')
        ->assertSet('reason', 'Pekerjaan cetak brosur')
        ->set('start_time', '18:00')
        ->set('end_time', '22:00')
        ->set('break', '')
        ->set('reason', 'Pekerjaan cetak brosur revisi desain')
        ->call('submitDateModal')
        ->assertSet('isDateModalOpen', false)
        ->assertSet('modalError', null);

    $overtime->refresh();
    expect(Carbon::parse($overtime->start_time)->format('H:i'))->toBe('18:00')
        ->and(Carbon::parse($overtime->end_time)->format('H:i'))->toBe('22:00')
        ->and($overtime->duration_hours)->toEqual(4.0)
        ->and($overtime->reason)->toBe('Pekerjaan cetak brosur revisi desain')
        ->and($overtime->status)->toBe('pending');
});

test('user cannot edit overtime if status is approved, rejected, or paid', function () {
    $user = User::factory()->create();

    $approvedOvertime = Overtime::create([
        'employee_id' => $user->id,
        'overtime_date' => '2026-09-18',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'duration_hours' => 3.0,
        'reason' => 'Lembur approved',
        'status' => 'approved',
    ]);

    $rejectedOvertime = Overtime::create([
        'employee_id' => $user->id,
        'overtime_date' => '2026-09-19',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'duration_hours' => 3.0,
        'reason' => 'Lembur rejected',
        'status' => 'rejected',
    ]);

    $paidOvertime = Overtime::create([
        'employee_id' => $user->id,
        'overtime_date' => '2026-09-20',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'duration_hours' => 3.0,
        'reason' => 'Lembur paid',
        'status' => 'paid',
    ]);

    $this->actingAs($user);

    // Test approved overtime
    Livewire::test(OvertimeComponent::class)
        ->call('editOvertime', $approvedOvertime->id)
        ->assertSet('isDateModalOpen', false)
        ->assertSet('editingOvertimeId', null);

    // Test rejected overtime
    Livewire::test(OvertimeComponent::class)
        ->call('editOvertime', $rejectedOvertime->id)
        ->assertSet('isDateModalOpen', false)
        ->assertSet('editingOvertimeId', null);

    // Test paid overtime
    Livewire::test(OvertimeComponent::class)
        ->call('editOvertime', $paidOvertime->id)
        ->assertSet('isDateModalOpen', false)
        ->assertSet('editingOvertimeId', null);
});

test('user cannot edit overtime belonging to another user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $overtimeB = Overtime::create([
        'employee_id' => $userB->id,
        'overtime_date' => '2026-09-21',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'duration_hours' => 3.0,
        'reason' => 'Lembur User B',
        'status' => 'pending',
    ]);

    $this->actingAs($userA);

    Livewire::test(OvertimeComponent::class)
        ->call('editOvertime', $overtimeB->id)
        ->assertSet('isDateModalOpen', false)
        ->assertSet('editingOvertimeId', null);
});

test('user cannot change overtime date to a date that already has an overtime', function () {
    $user = User::factory()->create();

    $overtime1 = Overtime::create([
        'employee_id' => $user->id,
        'overtime_date' => '2026-09-21',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'duration_hours' => 3.0,
        'reason' => 'Lembur 1',
        'status' => 'pending',
    ]);

    $overtime2 = Overtime::create([
        'employee_id' => $user->id,
        'overtime_date' => '2026-09-22',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'duration_hours' => 3.0,
        'reason' => 'Lembur 2',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(OvertimeComponent::class)
        ->call('editOvertime', $overtime1->id)
        ->set('overtime_date', '2026-09-22')
        ->call('submitDateModal')
        ->assertSet('isDateModalOpen', true)
        ->assertNotSet('modalError', null);
});

test('user can edit overtime keeping the same date without self-collision', function () {
    $user = User::factory()->create();

    $overtime = Overtime::create([
        'employee_id' => $user->id,
        'overtime_date' => '2026-09-21',
        'start_time' => '17:00',
        'end_time' => '20:00',
        'duration_hours' => 3.0,
        'reason' => 'Lembur 1',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(OvertimeComponent::class)
        ->call('editOvertime', $overtime->id)
        ->set('reason', 'Alasan diperbarui tanpa ubah tanggal')
        ->call('submitDateModal')
        ->assertSet('isDateModalOpen', false)
        ->assertSet('modalError', null);

    $overtime->refresh();
    expect($overtime->reason)->toBe('Alasan diperbarui tanpa ubah tanggal');
});
