<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EmployeeComponent;
use App\Livewire\Admin\MasterData\Admin;
use App\Livewire\Admin\OvertimeApprovalComponent;
use App\Livewire\Admin\ReplacementApprovalComponent;
use App\Livewire\Admin\WorkScheduleManagementComponent;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\Overtime;
use App\Models\ReplacementHour;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMultiDivisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_admin_with_multiple_divisions(): void
    {
        $superadmin = User::factory()->create(['group' => 'superadmin']);
        $div1 = Division::create(['name' => 'Produksi']);
        $div2 = Division::create(['name' => 'Marketing']);
        $jobTitle = JobTitle::create(['name' => 'Supervisor']);

        $this->actingAs($superadmin);

        Livewire::test(Admin::class)
            ->call('showCreating')
            ->set('form.name', 'Admin Multi Divisi')
            ->set('form.email', 'admin.multi@example.com')
            ->set('form.password', 'password123')
            ->set('form.nip', '1234567890')
            ->set('form.phone', '081234567890')
            ->set('form.group', 'admin')
            ->set('form.division_ids', [$div1->id, $div2->id])
            ->set('form.job_title_id', $jobTitle->id)
            ->call('create')
            ->assertHasNoErrors();

        $admin = User::where('email', 'admin.multi@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->group);
        $this->assertTrue($admin->hasMultipleDivisions());
        $this->assertEquals(2, $admin->adminDivisions()->count());
        $this->assertTrue($admin->hasDivisionAccess($div1->id));
        $this->assertTrue($admin->hasDivisionAccess($div2->id));
        $this->assertContains((int)$div1->id, $admin->getAccessibleDivisionIds());
        $this->assertContains((int)$div2->id, $admin->getAccessibleDivisionIds());
    }

    public function test_superadmin_can_update_admin_divisions(): void
    {
        $superadmin = User::factory()->create(['group' => 'superadmin']);
        $div1 = Division::create(['name' => 'Produksi']);
        $div2 = Division::create(['name' => 'Marketing']);
        $div3 = Division::create(['name' => 'Design']);
        $jobTitle = JobTitle::create(['name' => 'Supervisor']);

        $admin = User::factory()->create([
            'group' => 'admin',
            'division_id' => $div1->id,
        ]);
        $admin->adminDivisions()->sync([$div1->id]);

        $this->actingAs($superadmin);

        Livewire::test(Admin::class)
            ->call('edit', $admin->id)
            ->assertSet('form.division_ids', [$div1->id])
            ->set('form.division_ids', [$div2->id, $div3->id])
            ->call('update')
            ->assertHasNoErrors();

        $admin->refresh();
        $this->assertEquals(2, $admin->adminDivisions()->count());
        $this->assertFalse($admin->hasDivisionAccess($div1->id));
        $this->assertTrue($admin->hasDivisionAccess($div2->id));
        $this->assertTrue($admin->hasDivisionAccess($div3->id));
    }

    public function test_multi_division_admin_can_see_and_filter_only_assigned_divisions_in_employees(): void
    {
        $div1 = Division::create(['name' => 'Produksi']);
        $div2 = Division::create(['name' => 'Marketing']);
        $div3 = Division::create(['name' => 'Keuangan']);

        $admin = User::factory()->create(['group' => 'admin', 'division_id' => $div1->id]);
        $admin->adminDivisions()->sync([$div1->id, $div2->id]);

        $emp1 = User::factory()->create(['name' => 'Karyawan Produksi', 'division_id' => $div1->id, 'group' => 'user', 'status' => 'active']);
        $emp2 = User::factory()->create(['name' => 'Karyawan Marketing', 'division_id' => $div2->id, 'group' => 'user', 'status' => 'active']);
        $emp3 = User::factory()->create(['name' => 'Karyawan Keuangan', 'division_id' => $div3->id, 'group' => 'user', 'status' => 'active']);

        $this->actingAs($admin);

        // Can see emp1 and emp2, but NOT emp3
        Livewire::test(EmployeeComponent::class)
            ->assertSee('Karyawan Produksi')
            ->assertSee('Karyawan Marketing')
            ->assertDontSee('Karyawan Keuangan')
            // Filter by Divisi 1
            ->set('division', (string)$div1->id)
            ->assertSee('Karyawan Produksi')
            ->assertDontSee('Karyawan Marketing')
            // Filter by unauthorized Divisi 3 returns no results
            ->set('division', (string)$div3->id)
            ->assertDontSee('Karyawan Produksi')
            ->assertDontSee('Karyawan Marketing')
            ->assertDontSee('Karyawan Keuangan');
    }

    public function test_multi_division_admin_can_approve_overtimes_for_accessible_divisions_only(): void
    {
        $div1 = Division::create(['name' => 'Produksi']);
        $div2 = Division::create(['name' => 'Marketing']);
        $div3 = Division::create(['name' => 'Keuangan']);

        $admin = User::factory()->create(['group' => 'admin', 'division_id' => $div1->id]);
        $admin->adminDivisions()->sync([$div1->id, $div2->id]);

        $emp1 = User::factory()->create(['name' => 'Karyawan 1', 'division_id' => $div1->id, 'group' => 'user']);
        $emp2 = User::factory()->create(['name' => 'Karyawan 2', 'division_id' => $div2->id, 'group' => 'user']);
        $emp3 = User::factory()->create(['name' => 'Karyawan 3', 'division_id' => $div3->id, 'group' => 'user']);

        $ot1 = Overtime::create([
            'employee_id' => $emp1->id,
            'overtime_date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2,
            'status' => 'pending',
            'reason' => 'Lembur 1',
        ]);
        $ot2 = Overtime::create([
            'employee_id' => $emp2->id,
            'overtime_date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '20:00',
            'duration_hours' => 3,
            'status' => 'pending',
            'reason' => 'Lembur 2',
        ]);
        $ot3 = Overtime::create([
            'employee_id' => $emp3->id,
            'overtime_date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '18:00',
            'duration_hours' => 1,
            'status' => 'pending',
            'reason' => 'Lembur 3',
        ]);

        $this->actingAs($admin);

        // Admin can approve ot1 and ot2
        Livewire::test(OvertimeApprovalComponent::class)
            ->call('approve', $ot1->id)
            ->call('approve', $ot2->id);

        $this->assertEquals('approved', $ot1->fresh()->status);
        $this->assertEquals('approved', $ot2->fresh()->status);

        // Admin cannot approve ot3 (403 forbidden)
        Livewire::test(OvertimeApprovalComponent::class)
            ->call('approve', $ot3->id)
            ->assertStatus(403);
    }

    public function test_multi_division_admin_can_approve_replacements_for_accessible_divisions_only(): void
    {
        $div1 = Division::create(['name' => 'Produksi']);
        $div2 = Division::create(['name' => 'Marketing']);
        $div3 = Division::create(['name' => 'Keuangan']);
        $shift = Shift::create(['name' => 'Shift Pagi', 'start_time' => '08:00', 'end_time' => '16:00', 'division_id' => $div1->id]);

        $admin = User::factory()->create(['group' => 'admin', 'division_id' => $div1->id]);
        $admin->adminDivisions()->sync([$div1->id, $div2->id]);

        $emp1 = User::factory()->create(['name' => 'Karyawan 1', 'division_id' => $div1->id, 'group' => 'user']);
        $emp3 = User::factory()->create(['name' => 'Karyawan 3', 'division_id' => $div3->id, 'group' => 'user']);

        $rep1 = ReplacementHour::create([
            'user_id' => $emp1->id,
            'shift_id' => $shift->id,
            'replaced_date' => now()->toDateString(),
            'replacement_date' => now()->toDateString(),
            'start_hour' => '17:00',
            'end_hour' => '18:00',
            'duration_minutes' => 60,
            'status' => 'pending',
            'reason' => 'Ganti Jam 1',
        ]);
        $rep3 = ReplacementHour::create([
            'user_id' => $emp3->id,
            'shift_id' => $shift->id,
            'replaced_date' => now()->toDateString(),
            'replacement_date' => now()->toDateString(),
            'start_hour' => '17:00',
            'end_hour' => '18:00',
            'duration_minutes' => 60,
            'status' => 'pending',
            'reason' => 'Ganti Jam 3',
        ]);

        $this->actingAs($admin);

        // Admin can approve rep1
        Livewire::test(ReplacementApprovalComponent::class)
            ->call('approve', $rep1->id);

        $this->assertEquals('approved', $rep1->fresh()->status);

        // Admin cannot approve rep3
        Livewire::test(ReplacementApprovalComponent::class)
            ->call('approve', $rep3->id)
            ->assertStatus(403);
    }

    public function test_multi_division_admin_can_manage_work_schedules_for_accessible_divisions(): void
    {
        $div1 = Division::create(['name' => 'Produksi']);
        $div2 = Division::create(['name' => 'Marketing']);
        $div3 = Division::create(['name' => 'Keuangan']);

        $admin = User::factory()->create(['group' => 'admin', 'division_id' => $div1->id]);
        $admin->adminDivisions()->sync([$div1->id, $div2->id]);

        $emp1 = User::factory()->create(['name' => 'Karyawan 1', 'division_id' => $div1->id, 'group' => 'user']);
        $emp2 = User::factory()->create(['name' => 'Karyawan 2', 'division_id' => $div2->id, 'group' => 'user']);
        $emp3 = User::factory()->create(['name' => 'Karyawan 3', 'division_id' => $div3->id, 'group' => 'user']);

        $this->actingAs($admin);

        // Can create schedule for emp1 and emp2
        Livewire::test(WorkScheduleManagementComponent::class)
            ->set('start_date', '2026-09-01')
            ->set('end_date', '2026-09-01')
            ->set('user_ids', [$emp1->id, $emp2->id])
            ->set('is_working_day', true)
            ->set('note', 'Shift Rolling')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('work_schedules', ['user_id' => $emp1->id, 'date' => '2026-09-01']);
        $this->assertDatabaseHas('work_schedules', ['user_id' => $emp2->id, 'date' => '2026-09-01']);

        // Cannot create schedule for emp3 outside accessible divisions
        Livewire::test(WorkScheduleManagementComponent::class)
            ->set('start_date', '2026-09-01')
            ->set('end_date', '2026-09-01')
            ->set('user_ids', [$emp3->id])
            ->set('is_working_day', true)
            ->call('create')
            ->assertHasErrors(['user_ids']);
    }
}
