<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Education;
use App\Models\JobTitle;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerUserAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $edu = Education::firstOrCreate(['name' => 'S1']);
        $div = Division::firstOrCreate(['name' => 'Teknologi']);
        $job = JobTitle::firstOrCreate(['name' => 'Software Engineer']);

        $this->ownerUser = User::factory()->create([
            'group' => 'owner',
            'education_id' => $edu->id,
            'division_id' => $div->id,
            'job_title_id' => $job->id,
        ]);

        $this->employeeUser = User::factory()->create([
            'group' => 'user',
            'education_id' => $edu->id,
            'division_id' => $div->id,
            'job_title_id' => $job->id,
        ]);
    }

    public function test_owner_user_group_accessor()
    {
        $this->assertTrue($this->ownerUser->isOwner);
        $this->assertFalse($this->ownerUser->isPayroll);
        $this->assertFalse($this->ownerUser->isSyirkah);
        $this->assertFalse($this->ownerUser->isAdmin);
        $this->assertFalse($this->ownerUser->isSuperadmin);
        $this->assertFalse($this->ownerUser->isEmployee);
    }

    public function test_owner_user_redirects_to_payroll_dashboard_from_root()
    {
        $response = $this->actingAs($this->ownerUser)->get('/');
        $response->assertRedirect('/payroll');
    }

    public function test_owner_user_can_access_all_payroll_and_syirkah_pages()
    {
        $routes = [
            'payroll.dashboard',
            'payroll.history',
            'payroll.employee-salaries',
            'payroll.taxes',
            'payroll.error-deductions',
            'payroll.payment-methods',
            'payroll.savings',
            'payroll.saving-transactions',
            'payroll.loans',
            'payroll.flexible-deductions',
            'payroll.import-export.employee-salaries',
            'payroll.import-export.payment-methods',
            'payroll.import-export.savings',
            'payroll.import-export.saving-transactions',
            'payroll.export-bank',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->ownerUser)->get(route($route));
            $response->assertStatus(200);
        }
    }

    public function test_owner_user_can_approve_and_reject_saving_transactions()
    {
        $saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
        ]);

        $trx = SavingTransaction::create([
            'user_id' => $this->employeeUser->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
            'balance_mandatory' => 0,
            'balance_secondary' => 0,
            'status' => 'pending',
            'description' => 'Test mutasi pending',
        ]);

        Livewire::actingAs($this->ownerUser)
            ->test(\App\Livewire\Payroll\SavingTransactionComponent::class)
            ->call('approve', $trx->id);

        $this->assertEquals('approved', $trx->fresh()->status);
        $this->assertEquals($this->ownerUser->id, $trx->fresh()->approved_by);
    }

    public function test_owner_user_can_approve_and_reject_loans()
    {
        $loan = Loan::create([
            'user_id' => $this->employeeUser->id,
            'loan_amount' => 1000000,
            'remaining_balance' => 1000000,
            'tenor_months' => 5,
            'installment_amount' => 200000,
            'status' => 'pending',
            'payment_source' => 'payroll',
            'description' => 'Pinjaman tes',
        ]);

        Livewire::actingAs($this->ownerUser)
            ->test(\App\Livewire\Payroll\LoanComponent::class)
            ->call('approveLoan', $loan->id);

        $this->assertEquals('active', $loan->fresh()->status);
        $this->assertEquals($this->ownerUser->id, $loan->fresh()->approved_by);
    }
}
