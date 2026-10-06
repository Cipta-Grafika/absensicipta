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
use App\Services\SavingTransactionService;

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

    public function test_create_and_approve_loan_with_syirkah_pool_secondary_disbursement()
    {
        $this->actingAs($this->admin);

        Livewire::test(LoanComponent::class)
            ->set('user_id', $this->employee->id)
            ->set('loan_amount', 3120000)
            ->set('tenor_months', 6)
            ->set('payment_source', 'payroll')
            ->set('disbursement_source', 'syirkah_pool_secondary')
            ->set('syirkah_destination', 'syirkah_pool_secondary')
            ->set('description', 'Trip Singapore 2026 (SSR)')
            ->call('storeLoan');

        $loan = Loan::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals(3120000, $loan->loan_amount);
        $this->assertEquals(520000, $loan->installment_amount);
        $this->assertEquals('pending', $loan->status);
        $this->assertEquals('syirkah_pool_secondary', $loan->disbursement_source);
        $this->assertEquals('syirkah_pool_secondary', $loan->syirkah_destination);

        // Approve loan
        Livewire::test(LoanComponent::class)
            ->call('approveLoan', $loan->id);

        $loan->refresh();
        $this->assertEquals('active', $loan->status);
        // Syirkah Pool loans create a pool disbursement transaction on central syirkah ledger
        $this->assertNotNull($loan->saving_transaction_id);
        $tx = SavingTransaction::find($loan->saving_transaction_id);
        $this->assertNotNull($tx);
        $this->assertEquals('loan_disbursement_pool', $tx->reference_type);
        $this->assertEquals('withdrawal', $tx->transaction_type);
        $this->assertEquals(3120000, $tx->secondary_amount);

        // But employee's personal tabungan balance remains 0 (not negative or deducted)
        $this->assertEquals(0, \App\Models\SavingSummary::where('user_id', $this->employee->id)->value('total_secondary') ?? 0);
    }

    public function test_create_and_approve_loan_with_personal_syirkah_secondary_disbursement()
    {
        $this->actingAs($this->admin);

        SavingTransaction::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 500000,
            'secondary_amount' => 2000000,
            'status' => 'approved',
            'period_month' => '2026-09',
            'reference_type' => 'payroll',
            'description' => 'Saldo Awal',
        ]);
        SavingTransactionService::recalculateUserTransactions($this->employee->id);

        Livewire::test(LoanComponent::class)
            ->set('user_id', $this->employee->id)
            ->set('loan_amount', 1000000)
            ->set('tenor_months', 2)
            ->set('payment_source', 'payroll')
            ->set('disbursement_source', 'syirkah_secondary')
            ->set('syirkah_destination', 'syirkah_secondary')
            ->set('description', 'Pinjaman Potong Tabungan Pribadi')
            ->call('storeLoan');

        $loan = Loan::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals('syirkah_secondary', $loan->disbursement_source);
        $this->assertEquals('syirkah_secondary', $loan->syirkah_destination);

        // Approve loan
        Livewire::test(LoanComponent::class)
            ->call('approveLoan', $loan->id);

        $loan->refresh();
        $this->assertEquals('active', $loan->status);

        // Check withdrawal transaction created in personal syirkah
        $tx = SavingTransaction::find($loan->saving_transaction_id);
        $this->assertNotNull($tx);
        $this->assertEquals('withdrawal', $tx->transaction_type);
        $this->assertEquals(0, $tx->mandatory_amount);
        $this->assertEquals(1000000, $tx->secondary_amount);
    }

    public function test_payroll_paid_transition_decrements_loan_and_handles_pool_destination()
    {
        $this->actingAs($this->admin);

        // Create active loan with syirkah_pool_secondary destination
        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 3120000,
            'tenor_months' => 6,
            'installment_amount' => 520000,
            'remaining_balance' => 3120000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'syirkah_pool_secondary',
            'syirkah_destination' => 'syirkah_pool_secondary',
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
        // Syirkah pool repayments return to collective pool on central syirkah ledger
        $this->assertNotNull($installment->saving_transaction_id);
        $tx = SavingTransaction::find($installment->saving_transaction_id);
        $this->assertEquals('loan_installment_pool', $tx->reference_type);
        $this->assertEquals('deposit', $tx->transaction_type);
        $this->assertEquals(520000, $tx->secondary_amount);
    }

    public function test_payroll_paid_transition_with_mandatory_syirkah_destination()
    {
        $this->actingAs($this->admin);

        // Create active loan targeting personal mandatory syirkah
        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 3120000,
            'tenor_months' => 6,
            'installment_amount' => 520000,
            'remaining_balance' => 3120000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'syirkah_mandatory',
            'syirkah_destination' => 'syirkah_mandatory',
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

        $installment->refresh();
        $this->assertEquals('paid', $installment->status);
        $this->assertNotNull($installment->saving_transaction_id);

        // Verify deposit in Syirkah Wajib
        $depositTx = SavingTransaction::find($installment->saving_transaction_id);
        $this->assertNotNull($depositTx);
        $this->assertEquals('deposit', $depositTx->transaction_type);
        $this->assertEquals(520000, $depositTx->mandatory_amount);
        $this->assertEquals(0, $depositTx->secondary_amount);
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

    public function test_edit_pending_loan()
    {
        $this->actingAs($this->admin);

        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 2000000,
            'tenor_months' => 4,
            'installment_amount' => 500000,
            'remaining_balance' => 2000000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'syirkah_pool_secondary',
            'syirkah_destination' => 'syirkah_pool_secondary',
            'status' => 'pending',
            'description' => 'Kasbon Awal',
        ]);

        Livewire::test(LoanComponent::class)
            ->call('openEditModal', $loan->id)
            ->assertSet('isEditMode', true)
            ->assertSet('editingLoanId', $loan->id)
            ->assertSet('loan_amount', 2000000.0)
            ->assertSet('tenor_months', 4)
            ->assertSet('installment_amount', 500000.0)
            ->set('loan_amount', 6000000)
            ->set('tenor_months', 6)
            ->set('description', 'Kasbon Diperbarui')
            ->call('updateLoan');

        $loan->refresh();
        $this->assertEquals(6000000, $loan->loan_amount);
        $this->assertEquals(6, $loan->tenor_months);
        $this->assertEquals(1000000, $loan->installment_amount);
        $this->assertEquals(6000000, $loan->remaining_balance);
        $this->assertEquals('Kasbon Diperbarui', $loan->description);
        $this->assertEquals('pending', $loan->status);
    }

    public function test_edit_active_loan_updates_linked_syirkah_transaction()
    {
        $this->actingAs($this->admin);

        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 3000000,
            'tenor_months' => 3,
            'installment_amount' => 1000000,
            'remaining_balance' => 3000000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'syirkah_secondary',
            'syirkah_destination' => 'syirkah_secondary',
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approval_date' => now(),
            'description' => 'Kasbon Aktif',
        ]);

        $savingTx = SavingTransaction::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'transaction_type' => 'withdrawal',
            'mandatory_amount' => 0,
            'secondary_amount' => 3000000,
            'status' => 'approved',
            'period_month' => now()->format('Y-m'),
            'reference_type' => 'loan_disbursement',
            'reference_id' => $loan->id,
            'description' => 'Pencairan Pinjaman via Syirkah SSR Pribadi: Kasbon Aktif',
            'approved_by' => $this->admin->id,
            'approval_date' => now(),
        ]);

        $loan->update(['saving_transaction_id' => $savingTx->id]);

        Livewire::test(LoanComponent::class)
            ->call('openEditModal', $loan->id)
            ->assertSet('isEditMode', true)
            ->assertSet('editingLoanStatus', 'active')
            ->set('loan_amount', 4500000)
            ->set('tenor_months', 3)
            ->set('description', 'Kasbon Aktif Revisi')
            ->call('updateLoan');

        $loan->refresh();
        $this->assertEquals(4500000, $loan->loan_amount);
        $this->assertEquals(1500000, $loan->installment_amount);
        $this->assertEquals(4500000, $loan->remaining_balance);
        $this->assertEquals('Kasbon Aktif Revisi', $loan->description);

        $savingTx->refresh();
        $this->assertEquals(4500000, $savingTx->secondary_amount);
        $this->assertStringContainsString('Kasbon Aktif Revisi', $savingTx->description);
    }

    public function test_two_month_payroll_deduction_automatically_settles_loan_to_paid_off()
    {
        $this->actingAs($this->admin);

        // Employee has a 2 million loan with 2 months tenor (1 million per month)
        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 2000000,
            'tenor_months' => 2,
            'installment_amount' => 1000000,
            'remaining_balance' => 2000000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'company_cash',
            'syirkah_destination' => 'none',
            'status' => 'active',
            'approved_by' => $this->admin->id,
            'approval_date' => now(),
            'description' => 'Pinjaman 2 Juta',
        ]);

        // Month 1 Attendance & Payroll Generation
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-01',
            'status' => 'present',
            'time_in' => '08:00:00',
            'time_out' => '17:00:00',
        ]);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-09')
            ->set('generate_start_date', '2026-09-01')
            ->set('generate_end_date', '2026-09-30')
            ->set('generate_target', 'specific')
            ->set('selected_employee_ids', [$this->employee->id])
            ->call('generatePayroll');

        $payroll1 = Payroll::where('employee_id', $this->employee->id)->where('period_month', '2026-09')->first();
        $this->assertNotNull($payroll1);

        // Mark Month 1 as Paid
        Livewire::test(PayrollHistoryComponent::class)
            ->call('markAsPaid', $payroll1->id);

        $loan->refresh();
        $this->assertEquals(1000000, $loan->remaining_balance);
        $this->assertEquals('active', $loan->status);

        // Month 2 Attendance & Payroll Generation
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-10-01',
            'status' => 'present',
            'time_in' => '08:00:00',
            'time_out' => '17:00:00',
        ]);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-10')
            ->set('generate_start_date', '2026-10-01')
            ->set('generate_end_date', '2026-10-31')
            ->set('generate_target', 'specific')
            ->set('selected_employee_ids', [$this->employee->id])
            ->call('generatePayroll');

        $payroll2 = Payroll::where('employee_id', $this->employee->id)->where('period_month', '2026-10')->first();
        $this->assertNotNull($payroll2);

        // Mark Month 2 as Paid
        Livewire::test(PayrollHistoryComponent::class)
            ->call('markAsPaid', $payroll2->id);

        $loan->refresh();
        $this->assertEquals(0, $loan->remaining_balance);
        $this->assertEquals('paid_off', $loan->status);

        // Now test rendering LoanComponent ensures status is paid_off (LUNAS)
        Livewire::test(LoanComponent::class)
            ->assertSee('Lunas');
    }

    public function test_self_healing_sync_loans_fixes_out_of_sync_loans()
    {
        $this->actingAs($this->admin);

        // Loan initially shows 2 million active
        $loan = Loan::create([
            'user_id' => $this->employee->id,
            'loan_amount' => 2000000,
            'tenor_months' => 2,
            'installment_amount' => 1000000,
            'remaining_balance' => 2000000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'company_cash',
            'syirkah_destination' => 'none',
            'status' => 'active',
            'description' => 'Pinjaman Out of Sync',
        ]);

        // Two paid payrolls already exist with deduction details
        $p1 = Payroll::create([
            'employee_id' => $this->employee->id,
            'period_month' => '2026-08',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'basic_salary_earned' => 5000000,
            'total_allowance' => 1500000,
            'total_overtime_pay' => 0,
            'total_deduction' => 1000000,
            'net_salary' => 5500000,
            'status' => 'paid',
            'payment_date' => now(),
        ]);
        \App\Models\PayrollDetail::create([
            'payroll_id' => $p1->id,
            'type' => 'deduction',
            'name' => 'Cicilan Pinjaman',
            'amount' => 1000000,
        ]);

        $p2 = Payroll::create([
            'employee_id' => $this->employee->id,
            'period_month' => '2026-09',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'basic_salary_earned' => 5000000,
            'total_allowance' => 1500000,
            'total_overtime_pay' => 0,
            'total_deduction' => 1000000,
            'net_salary' => 5500000,
            'status' => 'paid',
            'payment_date' => now(),
        ]);
        \App\Models\PayrollDetail::create([
            'payroll_id' => $p2->id,
            'type' => 'deduction',
            'name' => 'Cicilan Pinjaman',
            'amount' => 1000000,
        ]);

        // Run sync
        \App\Services\LoanService::syncLoan($loan);

        $loan->refresh();
        $this->assertEquals(0, $loan->remaining_balance);
        $this->assertEquals('paid_off', $loan->status);
    }
}
