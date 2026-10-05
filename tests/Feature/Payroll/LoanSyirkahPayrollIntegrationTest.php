<?php

namespace Tests\Feature\Payroll;

use Tests\TestCase;
use App\Models\User;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payroll;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\Education;
use App\Models\EmployeeSalary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Payroll\LoanComponent;
use App\Livewire\Payroll\PayrollHistoryComponent;

class LoanSyirkahPayrollIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private Saving $saving;

    protected function setUp(): void
    {
        parent::setUp();

        $division = Division::create(['name' => 'IT Corporate']);
        $jobTitle = JobTitle::create(['name' => 'Software Engineer']);
        $education = Education::create(['name' => 'S1']);

        $this->saving = Saving::create([
            'savings_name' => 'Syirkah Reguler',
            'mandatory_savings' => 50000,
            'secondary_savings' => 100000,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Payroll',
            'nip' => 'ADM001',
            'group' => 'payroll',
            'division_id' => $division->id,
            'job_title_id' => $jobTitle->id,
            'education_id' => $education->id,
            'status' => 'active',
        ]);

        $this->employee = User::factory()->create([
            'name' => 'Rangga Jatnika',
            'nip' => 'EMP001',
            'group' => 'user',
            'division_id' => $division->id,
            'job_title_id' => $jobTitle->id,
            'education_id' => $education->id,
            'status' => 'active',
        ]);

        EmployeeSalary::create([
            'employee_id' => $this->employee->id,
            'basic_salary' => 5000000,
            'savings_id' => $this->saving->id,
            'meal_allowance' => 500000,
            'transport_allowance' => 500000,
            'attendance_allowance' => 500000,
            'salary_type' => 'monthly',
        ]);
    }

    public function test_create_and_approve_loan_with_syirkah_pool_disbursement()
    {
        $this->actingAs($this->admin);

        Livewire::test(LoanComponent::class)
            ->set('user_id', $this->employee->id)
            ->set('loan_amount', 3120000)
            ->set('tenor_months', 6)
            ->set('payment_source', 'payroll')
            ->set('disbursement_source', 'syirkah_pool')
            ->set('syirkah_destination', 'syirkah_secondary')
            ->set('description', 'Trip Singapore 2026')
            ->call('storeLoan');

        $loan = Loan::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals(3120000, $loan->loan_amount);
        $this->assertEquals(520000, $loan->installment_amount);
        $this->assertEquals('pending', $loan->status);
        $this->assertEquals('syirkah_pool', $loan->disbursement_source);
        $this->assertEquals('syirkah_secondary', $loan->syirkah_destination);

        // Approve loan
        Livewire::test(LoanComponent::class)
            ->call('approveLoan', $loan->id);

        $loan->refresh();
        $this->assertEquals('active', $loan->status);
        $this->assertNotNull($loan->saving_transaction_id);

        // Check withdrawal transaction created in syirkah
        $tx = SavingTransaction::find($loan->saving_transaction_id);
        $this->assertNotNull($tx);
        $this->assertEquals('withdrawal', $tx->transaction_type);
        $this->assertEquals(3120000, $tx->secondary_amount);
        $this->assertEquals('loan_disbursement', $tx->reference_type);
    }

    public function test_payroll_paid_transition_decrements_loan_and_creates_syirkah_deposit()
    {
        $this->actingAs($this->admin);

        // Create active loan
        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 3120000,
            'tenor_months' => 6,
            'installment_amount' => 520000,
            'remaining_balance' => 3120000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'syirkah_pool',
            'syirkah_destination' => 'syirkah_secondary',
            'status' => 'active',
            'approved_by' => $this->admin->id,
            'approval_date' => now(),
            'description' => 'Trip Singapore 2026',
        ]);

        // Create draft payroll
        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'period_month' => '2026-10',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'basic_salary_earned' => 5000000,
            'total_allowance' => 1500000,
            'total_overtime_pay' => 0,
            'total_deduction' => 520000,
            'net_salary' => 5980000,
            'status' => 'draft',
        ]);

        $installment = LoanInstallment::create([
            'loan_id' => $loan->id,
            'amount_paid' => 520000,
            'payment_method' => 'payroll_deduction',
            'payroll_id' => $payroll->id,
            'status' => 'pending',
        ]);

        // Mark payroll as paid
        Livewire::test(PayrollHistoryComponent::class)
            ->call('markAsPaid', $payroll->id);

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->status);

        $loan->refresh();
        $this->assertEquals(2600000, $loan->remaining_balance);
        $this->assertEquals('active', $loan->status);

        $installment->refresh();
        $this->assertEquals('paid', $installment->status);
        $this->assertNotNull($installment->saving_transaction_id);

        // Verify deposit in Syirkah
        $depositTx = SavingTransaction::find($installment->saving_transaction_id);
        $this->assertNotNull($depositTx);
        $this->assertEquals('deposit', $depositTx->transaction_type);
        $this->assertEquals(520000, $depositTx->secondary_amount);
        $this->assertEquals('approved', $depositTx->status);
    }

    public function test_delete_paid_payroll_rollbacks_loan_and_cleans_syirkah_deposit()
    {
        $this->actingAs($this->admin);

        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 520000,
            'tenor_months' => 1,
            'installment_amount' => 520000,
            'remaining_balance' => 0,
            'payment_source' => 'payroll',
            'disbursement_source' => 'syirkah_pool',
            'syirkah_destination' => 'syirkah_secondary',
            'status' => 'paid_off',
            'approved_by' => $this->admin->id,
            'approval_date' => now(),
            'description' => 'Test Single Installment',
        ]);

        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'period_month' => '2026-10',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'basic_salary_earned' => 5000000,
            'total_allowance' => 1500000,
            'total_overtime_pay' => 0,
            'total_deduction' => 520000,
            'net_salary' => 5980000,
            'status' => 'paid',
        ]);

        $savingTx = SavingTransaction::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 0,
            'secondary_amount' => 520000,
            'status' => 'approved',
            'period_month' => '2026-10',
            'reference_type' => 'loan_installment',
            'reference_id' => '01m45testinst0001',
            'description' => 'Setoran Cicilan',
        ]);

        $installment = LoanInstallment::create([
            'id' => '01m45testinst0001',
            'loan_id' => $loan->id,
            'amount_paid' => 520000,
            'payment_method' => 'payroll_deduction',
            'payroll_id' => $payroll->id,
            'saving_transaction_id' => $savingTx->id,
            'status' => 'paid',
        ]);

        // Delete the payroll
        Livewire::test(PayrollHistoryComponent::class)
            ->set('payrollIdToDelete', $payroll->id)
            ->call('deletePayroll');

        $this->assertNull(Payroll::find($payroll->id));
        $this->assertNull(LoanInstallment::find($installment->id));
        $this->assertNull(SavingTransaction::find($savingTx->id));

        $loan->refresh();
        $this->assertEquals(520000, $loan->remaining_balance);
        $this->assertEquals('active', $loan->status);
    }
}
