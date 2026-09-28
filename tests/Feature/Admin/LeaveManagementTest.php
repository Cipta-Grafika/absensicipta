<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\LeaveManagementComponent;
use App\Livewire\User\ApplyLeaveModalComponent;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $admin;
    protected User $employee;
    protected Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->division = Division::create(['name' => 'IT Department']);

        $this->superadmin = User::factory()->create([
            'group' => 'superadmin',
            'division_id' => $this->division->id,
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'group' => 'admin',
            'division_id' => $this->division->id,
            'status' => 'active',
        ]);

        $this->employee = User::factory()->create([
            'group' => 'user',
            'division_id' => $this->division->id,
            'status' => 'active',
        ]);
    }

    public function test_superadmin_can_access_leave_management_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get('/hr/leave-management');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Cuti Karyawan');
    }

    public function test_regular_admin_and_user_cannot_access_leave_management_page(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get('/hr/leave-management');
        $responseAdmin->assertStatus(403);

        $responseUser = $this->actingAs($this->employee)->get('/hr/leave-management');
        $responseUser->assertRedirect(route('home'));
    }

    public function test_leave_balance_calculation_and_attributes(): void
    {
        $year = (int) date('Y');
        $balance = EmployeeLeaveBalance::getOrCreateForUser($this->employee, $year);

        $balance->update([
            'initial_quota' => 12,
            'carry_forward' => 3,
            'adjustment' => 2,
        ]);

        $this->assertEquals(17, $balance->total_quota);
        $this->assertEquals(0, $balance->used_quota);
        $this->assertEquals(17, $balance->remaining_quota);
        $this->assertEquals(0.0, $balance->usage_percentage);
    }

    public function test_attendance_with_status_leave_automatically_deducts_leave_balance(): void
    {
        $year = 2026;
        $balance = EmployeeLeaveBalance::getOrCreateForUser($this->employee, $year);
        $balance->update(['initial_quota' => 12, 'carry_forward' => 0, 'adjustment' => 0]);

        $this->assertEquals(12, $balance->remaining_quota);

        // Create 2 attendance records with status leave
        $att1 = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-05-10',
            'status' => 'leave',
            'note' => 'Cuti liburan keluarga',
        ]);

        $att2 = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-05-11',
            'status' => 'leave',
            'note' => 'Cuti liburan keluarga',
        ]);

        $balance->refresh();
        $this->assertEquals(2, $balance->used_quota);
        $this->assertEquals(10, $balance->remaining_quota);

        // Deleting one attendance record restores quota automatically
        $att1->delete();
        $balance->refresh();
        $this->assertEquals(1, $balance->used_quota);
        $this->assertEquals(11, $balance->remaining_quota);
    }

    public function test_superadmin_can_adjust_employee_leave_quota(): void
    {
        $year = (int) date('Y');

        Livewire::actingAs($this->superadmin)
            ->test(LeaveManagementComponent::class)
            ->call('openAdjustModal', $this->employee->id)
            ->set('formInitialQuota', 15)
            ->set('formCarryForward', 2)
            ->set('formAdjustment', -1)
            ->set('formNote', 'Penyesuaian kuota awal tahun')
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        $balance = EmployeeLeaveBalance::where('user_id', $this->employee->id)->where('year', $year)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(15, $balance->initial_quota);
        $this->assertEquals(2, $balance->carry_forward);
        $this->assertEquals(-1, $balance->adjustment);
        $this->assertEquals(16, $balance->total_quota);
        $this->assertEquals('Penyesuaian kuota awal tahun', $balance->note);
    }

    public function test_superadmin_can_record_leave_directly_for_employee(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(LeaveManagementComponent::class)
            ->call('openAddLeaveModal', $this->employee->id)
            ->set('addLeaveUserId', $this->employee->id)
            ->set('addLeaveStatus', 'leave')
            ->set('addLeaveFrom', '2026-07-01')
            ->set('addLeaveTo', '2026-07-02')
            ->set('addLeaveNote', 'Cuti dinas/pribadi disetujui Superadmin')
            ->call('saveAddLeave')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee->id,
            'date' => '2026-07-01',
            'status' => 'leave',
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee->id,
            'date' => '2026-07-02',
            'status' => 'leave',
        ]);

        $balance = EmployeeLeaveBalance::getOrCreateForUser($this->employee, 2026);
        $this->assertEquals(2, $balance->used_quota);
    }

    public function test_user_apply_leave_modal_validates_insufficient_leave_quota(): void
    {
        $year = 2026;
        $balance = EmployeeLeaveBalance::getOrCreateForUser($this->employee, $year);
        // Set remaining quota to only 1 day
        $balance->update(['initial_quota' => 1, 'carry_forward' => 0, 'adjustment' => 0, 'used_quota' => 0]);

        // Trying to apply for 3 days cuti
        Livewire::actingAs($this->employee)
            ->test(ApplyLeaveModalComponent::class)
            ->call('openCutiModal')
            ->set('status', 'leave')
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-03')
            ->set('note', 'Permohonan cuti panjang')
            ->call('submit')
            ->assertHasErrors(['from']);

        // Applying for 1 day succeeds
        Livewire::actingAs($this->employee)
            ->test(ApplyLeaveModalComponent::class)
            ->call('openCutiModal')
            ->set('status', 'leave')
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-01')
            ->set('note', 'Permohonan cuti 1 hari')
            ->call('submit')
            ->assertHasNoErrors();

        $balance->refresh();
        $this->assertEquals(1, $balance->used_quota);
        $this->assertEquals(0, $balance->remaining_quota);
    }

    public function test_master_leave_type_crud(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(LeaveManagementComponent::class)
            ->call('openManageLeaveTypesModal')
            ->set('typeCode', 'TEST_LEAVE')
            ->set('typeName', 'Cuti Uji Coba')
            ->set('typeDefaultDays', 5)
            ->set('typeDeductsQuota', false)
            ->set('typeRequiresAttachment', true)
            ->set('typeIsActive', true)
            ->call('saveLeaveType')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('leave_types', [
            'code' => 'TEST_LEAVE',
            'name' => 'Cuti Uji Coba',
            'default_days' => 5,
        ]);
    }

    public function test_leave_management_pdf_export(): void
    {
        $component = Livewire::actingAs($this->superadmin)
            ->test(LeaveManagementComponent::class);

        $response = $component->call('exportPdf');
        $this->assertNotNull($response);
    }

    public function test_header_events_trigger_actions_and_modals(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(LeaveManagementComponent::class)
            ->dispatch('open-add-leave-modal')
            ->assertSet('isAddLeaveModalOpen', true)
            ->dispatch('open-manage-leave-types-modal')
            ->assertSet('isLeaveTypesModalOpen', true)
            ->dispatch('bulk-sync-all')
            ->assertHasNoErrors();
    }

    public function test_annual_leave_tracks_all_months_across_entire_year(): void
    {
        $year = 2026;
        $employee = User::factory()->create(['group' => 'user', 'status' => 'active']);

        // Create leave records spanning multiple past months across 2026
        Attendance::create(['user_id' => $employee->id, 'date' => '2026-01-15', 'status' => 'leave', 'note' => 'Cuti Januari']);
        Attendance::create(['user_id' => $employee->id, 'date' => '2026-03-20', 'status' => 'leave', 'note' => 'Cuti Maret']);
        Attendance::create(['user_id' => $employee->id, 'date' => '2026-06-10', 'status' => 'leave', 'note' => 'Cuti Juni']);
        Attendance::create(['user_id' => $employee->id, 'date' => '2026-09-05', 'status' => 'leave', 'note' => 'Cuti September']);
        // Leave in another year (2025) should not be counted in 2026
        Attendance::create(['user_id' => $employee->id, 'date' => '2025-11-12', 'status' => 'leave', 'note' => 'Cuti 2025']);

        // Synchronize for 2026
        EmployeeLeaveBalance::syncAllForYear($year);

        $balance2026 = $employee->leaveBalanceForYear(2026);
        $this->assertEquals(4, $balance2026->used_quota, 'Harus menghitung 4 hari cuti dari bulan Jan, Mar, Jun, Sep 2026.');
        $this->assertEquals(12 - 4, $balance2026->remaining_quota);

        // Verify history modal loads all 4 records across the year
        Livewire::actingAs($this->superadmin)
            ->test(LeaveManagementComponent::class)
            ->set('year', 2026)
            ->call('openHistoryModal', $employee->id)
            ->assertSet('isHistoryModalOpen', true)
            ->assertCount('userLeaveHistory', 4);
    }
}
