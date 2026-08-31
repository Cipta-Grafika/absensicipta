<?php

namespace Tests\Feature\Payroll;

use App\Livewire\Payroll\ErrorDeductionComponent;
use App\Livewire\Payroll\PayrollHistoryComponent;
use App\Models\ErrorDeduction;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ErrorDeductionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_user_and_superadmin_can_access_error_deduction_page()
    {
        $payrollUser = User::factory()->create(['group' => 'payroll']);
        $superadmin = User::factory()->create(['group' => 'superadmin']);
        $regularUser = User::factory()->create(['group' => 'user']);

        $response = $this->actingAs($payrollUser)->get(route('payroll.error-deductions'));
        $response->assertStatus(200);

        $response = $this->actingAs($superadmin)->get(route('payroll.error-deductions'));
        $response->assertStatus(200);

        $response = $this->actingAs($regularUser)->get(route('payroll.error-deductions'));
        $response->assertStatus(403);
    }

    public function test_payroll_user_can_create_edit_and_delete_error_deduction()
    {
        $admin = User::factory()->create(['group' => 'payroll']);
        $employee = User::factory()->create(['name' => 'Budi Santoso', 'nip' => 'EMP-001', 'status' => 'active']);
        $this->actingAs($admin);

        // 1. Create
        Livewire::test(ErrorDeductionComponent::class)
            ->call('openCreateModal')
            ->set('user_id', $employee->id)
            ->set('period_month', '2026-08')
            ->set('error_date', '2026-08-15')
            ->set('error_title', 'Salah Cetak Banner 10m')
            ->set('description', 'Salah ukuran resolusi dari file customer SPK #8891')
            ->set('total_error_cost', 500000)
            ->set('amount', 250000)
            ->set('form_deduction_source', 'payroll')
            ->set('form_status', 'pending')
            ->call('save')
            ->assertHasNoErrors();

        $error = ErrorDeduction::where('user_id', $employee->id)->where('period_month', '2026-08')->first();
        $this->assertNotNull($error);
        $this->assertEquals('Salah Cetak Banner 10m', $error->error_title);
        $this->assertEquals(250000, $error->amount);
        $this->assertEquals('payroll', $error->deduction_source);
        $this->assertEquals('pending', $error->status);

        // 2. Edit
        Livewire::test(ErrorDeductionComponent::class)
            ->call('edit', $error->id)
            ->assertSet('error_title', 'Salah Cetak Banner 10m')
            ->set('amount', 300000)
            ->set('form_status', 'approved')
            ->call('save')
            ->assertHasNoErrors();

        $error->refresh();
        $this->assertEquals(300000, $error->amount);
        $this->assertEquals('approved', $error->status);

        // 3. Quick Status Update
        Livewire::test(ErrorDeductionComponent::class)
            ->call('updateStatus', $error->id, 'pending');

        $error->refresh();
        $this->assertEquals('pending', $error->status);

        // 4. Delete
        Livewire::test(ErrorDeductionComponent::class)
            ->call('confirmDelete', $error->id)
            ->call('delete');

        $this->assertDatabaseMissing('error_deductions', ['id' => $error->id]);
    }

    public function test_payroll_generation_applies_error_deduction_and_bpjs_deduction()
    {
        $admin = User::factory()->create(['group' => 'payroll']);
        $this->actingAs($admin);

        $shift = Shift::create([
            'name' => 'Shift Normal',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $employee = User::factory()->create(['name' => 'Andi Pratama', 'status' => 'active']);

        // Set Salary with BPJS Rp 150.000 and Basic Salary Rp 3.000.000
        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'salary_type' => 'monthly',
            'basic_salary' => 3000000,
            'working_days_per_month' => 25,
            'bpjs' => 150000,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'overtime_rate' => 0,
            'late_deduction_rate' => 0,
            'annual_leave_quota' => 12,
        ]);

        // Create Error Deduction of Rp 200.000 for 2026-08
        $errorDeduction = ErrorDeduction::create([
            'user_id' => $employee->id,
            'period_month' => '2026-08',
            'error_date' => '2026-08-10',
            'error_title' => 'Kerusakan Mesin Cutting',
            'description' => 'Mata pisau patah karena kelalaian',
            'total_error_cost' => 400000,
            'amount' => 200000,
            'deduction_source' => 'payroll',
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        // Add 25 present attendances and 6 off days in 2026-08
        for ($day = 1; $day <= 31; $day++) {
            $dateStr = sprintf('2026-08-%02d', $day);
            $isWork = ($day <= 25);
            WorkSchedule::create([
                'user_id' => $employee->id,
                'date' => $dateStr,
                'is_working_day' => $isWork,
            ]);
            if ($isWork) {
                Attendance::create([
                    'user_id' => $employee->id,
                    'work_schedule_id' => null,
                    'date' => $dateStr,
                    'time_in' => '08:00:00',
                    'time_out' => '17:00:00',
                    'status' => 'present',
                ]);
            }
        }

        // Generate Payroll for 2026-08
        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $payroll = Payroll::where('employee_id', $employee->id)->where('period_month', '2026-08')->first();
        $this->assertNotNull($payroll);

        // Expected Deductions:
        // BPJS = 150.000
        // Error Deduction = 200.000
        // Total Deductions = 350.000
        // Basic Salary = 3.000.000
        // Net Salary = 2.650.000
        $this->assertEquals(350000, (int) $payroll->total_deduction);
        $this->assertEquals(2650000, (int) $payroll->net_salary);

        // Check details table
        $bpjsDetail = $payroll->details->where('name', 'Potongan BPJS')->first();
        $this->assertNotNull($bpjsDetail);
        $this->assertEquals(150000, (int) $bpjsDetail->amount);

        $errorDetail = $payroll->details->where('name', 'Potongan Error: Kerusakan Mesin Cutting')->first();
        $this->assertNotNull($errorDetail);
        $this->assertEquals(200000, (int) $errorDetail->amount);

        // Check error deduction status updated to processed and linked
        $errorDeduction->refresh();
        $this->assertEquals('processed', $errorDeduction->status);
        $this->assertTrue($errorDeduction->is_applied);
        $this->assertEquals($payroll->id, $errorDeduction->payroll_id);

        // Test Delete Payroll resets the Error Deduction
        Livewire::test(PayrollHistoryComponent::class)
            ->set('payrollIdToDelete', $payroll->id)
            ->call('deletePayroll');

        $this->assertDatabaseMissing('payrolls', ['id' => $payroll->id]);
        $errorDeduction->refresh();
        $this->assertEquals('approved', $errorDeduction->status);
        $this->assertFalse($errorDeduction->is_applied);
        $this->assertNull($errorDeduction->payroll_id);
    }

    public function test_syirkah_error_deduction_creates_withdrawal_mutation()
    {
        $admin = User::factory()->create(['group' => 'payroll']);
        $this->actingAs($admin);

        $employee = User::factory()->create(['name' => 'Citra Dewi', 'status' => 'active']);
        $saving = Saving::create(['name' => 'Koperasi Syirkah Bersama']);

        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'salary_type' => 'monthly',
            'basic_salary' => 4000000,
            'working_days_per_month' => 25,
            'savings_id' => $saving->id,
            'custom_secondary_savings' => 0,
        ]);

        // Employee has deposited secondary savings of Rp 500.000
        SavingTransaction::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 100000,
            'secondary_amount' => 500000,
            'status' => 'approved',
            'period_month' => '2026-07',
        ]);

        // Error deduction via Syirkah SSR of Rp 120.000
        $errorDeduction = ErrorDeduction::create([
            'user_id' => $employee->id,
            'period_month' => '2026-08',
            'error_date' => '2026-08-12',
            'error_title' => 'Salah Cetak Nota',
            'description' => 'Salah cetak 2 rim',
            'total_error_cost' => 120000,
            'amount' => 120000,
            'deduction_source' => 'syirkah_secondary',
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        for ($day = 1; $day <= 31; $day++) {
            $dateStr = sprintf('2026-08-%02d', $day);
            $isWork = ($day <= 25);
            WorkSchedule::create([
                'user_id' => $employee->id,
                'date' => $dateStr,
                'is_working_day' => $isWork,
            ]);
            if ($isWork) {
                Attendance::create([
                    'user_id' => $employee->id,
                    'work_schedule_id' => null,
                    'date' => $dateStr,
                    'time_in' => '08:00:00',
                    'time_out' => '17:00:00',
                    'status' => 'present',
                ]);
            }
        }

        // Generate Payroll
        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $errorDeduction->refresh();
        $this->assertEquals('processed', $errorDeduction->status);
        $this->assertTrue($errorDeduction->is_applied);
        $this->assertNotNull($errorDeduction->saving_transaction_id);

        $st = SavingTransaction::find($errorDeduction->saving_transaction_id);
        $this->assertNotNull($st);
        $this->assertEquals('withdrawal', $st->transaction_type);
        $this->assertEquals(120000, (int) $st->secondary_amount);
        $this->assertEquals('error_deduction', $st->reference_type);
    }
}
