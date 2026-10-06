<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\OvertimeApprovalComponent;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\Overtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OvertimeApprovalFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_list_excludes_paid_overtimes_and_shows_only_when_filtered(): void
    {
        $superadmin = User::factory()->create(['group' => 'superadmin']);
        $division = Division::create(['name' => 'Produksi']);
        $jobTitle = JobTitle::create(['name' => 'Operator']);

        $employee = User::factory()->create([
            'division_id' => $division->id,
            'job_title_id' => $jobTitle->id,
            'name' => 'Budi Santoso',
            'nip' => 'EMP001',
        ]);

        // Create 4 overtimes with different statuses
        $pending = Overtime::create([
            'employee_id' => $employee->id,
            'overtime_date' => now()->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '20:00:00',
            'duration_hours' => 3,
            'status' => 'pending',
            'reason' => 'Lembur Urgent Pending',
        ]);

        $approved = Overtime::create([
            'employee_id' => $employee->id,
            'overtime_date' => now()->subDay()->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '19:00:00',
            'duration_hours' => 2,
            'status' => 'approved',
            'reason' => 'Lembur Approved',
        ]);

        $rejected = Overtime::create([
            'employee_id' => $employee->id,
            'overtime_date' => now()->subDays(2)->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'duration_hours' => 1,
            'status' => 'rejected',
            'reason' => 'Lembur Ditolak',
        ]);

        $paid = Overtime::create([
            'employee_id' => $employee->id,
            'overtime_date' => now()->subDays(3)->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '21:00:00',
            'duration_hours' => 4,
            'status' => 'paid',
            'reason' => 'Lembur Sudah Cair Paid',
        ]);

        $this->actingAs($superadmin);

        // 1. Default view (statusFilter is empty): should show pending, approved, rejected, but NOT paid
        Livewire::test(OvertimeApprovalComponent::class)
            ->assertSee('Lembur Urgent Pending')
            ->assertSee('Lembur Approved')
            ->assertSee('Lembur Ditolak')
            ->assertDontSee('Lembur Sudah Cair Paid');

        // 2. Filter status=paid: should show ONLY paid
        Livewire::test(OvertimeApprovalComponent::class, ['statusFilter' => 'paid'])
            ->assertSee('Lembur Sudah Cair Paid')
            ->assertDontSee('Lembur Urgent Pending')
            ->assertDontSee('Lembur Approved')
            ->assertDontSee('Lembur Ditolak');

        // 3. Filter status=pending: should show ONLY pending
        Livewire::test(OvertimeApprovalComponent::class)
            ->set('statusFilter', 'pending')
            ->assertSee('Lembur Urgent Pending')
            ->assertDontSee('Lembur Approved')
            ->assertDontSee('Lembur Ditolak')
            ->assertDontSee('Lembur Sudah Cair Paid');

        // 4. Filter status=all: should show ALL statuses including paid
        Livewire::test(OvertimeApprovalComponent::class, ['statusFilter' => 'all'])
            ->assertSee('Lembur Urgent Pending')
            ->assertSee('Lembur Approved')
            ->assertSee('Lembur Ditolak')
            ->assertSee('Lembur Sudah Cair Paid');
    }
}
