<?php

namespace Tests\Feature\Payroll;

use App\Livewire\Payroll\PayrollDashboardComponent;
use App\Models\Payroll;
use App\Models\Saving;
use App\Models\SavingSummary;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Services\SavingTransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollDashboardSyirkahSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_payroll_dashboard_and_see_syirkah_summary()
    {
        $owner = User::factory()->create(['group' => 'owner']);
        $employee = User::factory()->create(['group' => 'user']);
        $saving = Saving::create([
            'savings_name' => 'Syirkah Karyawan',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
        ]);

        $currentMonth = '2026-09';
        $periodDate = Carbon::parse('2026-09-01')->endOfMonth();

        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'period_month' => $currentMonth,
            'start_date' => '2026-08-26',
            'end_date' => '2026-09-25',
            'basic_salary_earned' => 3000000,
            'total_allowance' => 500000,
            'total_overtime_pay' => 0,
            'total_deduction' => 75000,
            'net_salary' => 3425000,
            'status' => 'draft',
        ]);

        $tx = SavingTransaction::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
            'reference_type' => 'payroll',
            'reference_id' => $payroll->id,
            'description' => 'Potongan Syirkah Payroll ' . $currentMonth,
            'status' => 'pending',
            'created_at' => $periodDate,
            'updated_at' => $periodDate,
        ]);

        $this->actingAs($owner);

        Livewire::test(PayrollDashboardComponent::class)
            ->set('month', $currentMonth)
            ->assertSee('Ringkasan & Persetujuan Syirkah', false)
            ->assertSee('Perlu Persetujuan')
            ->assertSee('1') // 1 mutasi pending
            ->assertSee('Setujui Semua (1)')
            ->assertSee('75.000');
    }

    public function test_owner_can_bulk_approve_payroll_syirkah_from_dashboard()
    {
        $owner = User::factory()->create(['group' => 'owner']);
        $emp1 = User::factory()->create(['group' => 'user']);
        $emp2 = User::factory()->create(['group' => 'user']);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Karyawan',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
        ]);

        $currentMonth = '2026-09';
        $periodDate = Carbon::parse('2026-09-01')->endOfMonth();

        $payroll1 = Payroll::create([
            'employee_id' => $emp1->id,
            'period_month' => $currentMonth,
            'start_date' => '2026-08-26',
            'end_date' => '2026-09-25',
            'basic_salary_earned' => 3000000,
            'total_allowance' => 0,
            'total_overtime_pay' => 0,
            'total_deduction' => 75000,
            'net_salary' => 2925000,
            'status' => 'draft',
        ]);

        $payroll2 = Payroll::create([
            'employee_id' => $emp2->id,
            'period_month' => $currentMonth,
            'start_date' => '2026-08-26',
            'end_date' => '2026-09-25',
            'basic_salary_earned' => 3000000,
            'total_allowance' => 0,
            'total_overtime_pay' => 0,
            'total_deduction' => 75000,
            'net_salary' => 2925000,
            'status' => 'draft',
        ]);

        $tx1 = SavingTransaction::create([
            'user_id' => $emp1->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
            'reference_type' => 'payroll',
            'reference_id' => $payroll1->id,
            'description' => 'Potongan Syirkah Payroll ' . $currentMonth,
            'status' => 'pending',
            'created_at' => $periodDate,
            'updated_at' => $periodDate,
        ]);

        $tx2 = SavingTransaction::create([
            'user_id' => $emp2->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
            'reference_type' => 'payroll',
            'reference_id' => $payroll2->id,
            'description' => 'Potongan Syirkah Payroll ' . $currentMonth,
            'status' => 'pending',
            'created_at' => $periodDate,
            'updated_at' => $periodDate,
        ]);

        $this->actingAs($owner);

        Livewire::test(PayrollDashboardComponent::class)
            ->set('month', $currentMonth)
            ->call('openBulkApproveModal', 'current_month')
            ->assertSet('bulkApproveModalOpen', true)
            ->call('confirmBulkApprove')
            ->assertSet('bulkApproveModalOpen', false)
            ->assertDispatched('notify');

        // Check transactions are approved
        $this->assertEquals('approved', $tx1->fresh()->status);
        $this->assertEquals($owner->id, $tx1->fresh()->approved_by);
        $this->assertEquals('approved', $tx2->fresh()->status);
        $this->assertEquals($owner->id, $tx2->fresh()->approved_by);

        // Check running balances updated
        $this->assertEquals(50000, (float) $tx1->fresh()->balance_mandatory);
        $this->assertEquals(25000, (float) $tx1->fresh()->balance_secondary);

        // Check SavingSummary updated
        $summary1 = SavingSummary::where('user_id', $emp1->id)->where('savings_id', $saving->id)->first();
        $this->assertNotNull($summary1);
        $this->assertEquals(50000, (float) $summary1->total_mandatory);
        $this->assertEquals(25000, (float) $summary1->total_secondary);
    }

    public function test_superadmin_cannot_bulk_approve_syirkah()
    {
        $superadmin = User::factory()->create(['group' => 'superadmin']);
        $this->actingAs($superadmin);

        Livewire::test(PayrollDashboardComponent::class)
            ->call('openBulkApproveModal')
            ->assertStatus(403);
    }
}
