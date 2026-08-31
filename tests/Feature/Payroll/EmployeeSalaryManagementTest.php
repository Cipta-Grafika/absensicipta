<?php

namespace Tests\Feature\Payroll;

use App\Livewire\Payroll\EmployeeSalaryComponent;
use App\Models\EmployeeSalary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeSalaryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_user_can_set_and_update_bpjs_and_pph21_on_employee_salary()
    {
        $admin = User::factory()->create(['group' => 'payroll']);
        $employee = User::factory()->create(['group' => 'user', 'status' => 'active']);

        $this->actingAs($admin);

        // Test saving with bpjs and pph21
        Livewire::test(EmployeeSalaryComponent::class)
            ->set('employee_id', $employee->id)
            ->set('salary_type', 'monthly')
            ->set('working_days_per_month', 25)
            ->set('basic_salary', 3000000)
            ->set('bpjs', 150000)
            ->set('pph21', 50000)
            ->call('save');

        $salary = EmployeeSalary::where('employee_id', $employee->id)->first();
        $this->assertNotNull($salary);
        $this->assertEquals(150000, $salary->bpjs);
        $this->assertEquals(50000, $salary->pph21);

        // Test editing loads the bpjs and pph21 correctly
        Livewire::test(EmployeeSalaryComponent::class)
            ->call('edit', $employee->id)
            ->assertSet('bpjs', 150000)
            ->assertSet('pph21', 50000)
            ->set('bpjs', 0)
            ->set('pph21', 0)
            ->call('save');

        $salary->refresh();
        $this->assertEquals(0, $salary->bpjs);
        $this->assertEquals(0, $salary->pph21);
    }

    public function test_payroll_user_can_assign_tax_master_pph21_on_employee_salary()
    {
        $admin = User::factory()->create(['group' => 'payroll']);
        $employee = User::factory()->create(['group' => 'user', 'status' => 'active']);

        $taxMaster = \App\Models\TaxMaster::create([
            'category' => 'TER A',
            'code' => 'TER-A-01',
            'name' => 'TER A - s.d Rp 5.400.000 (0%)',
            'min_gross_income' => 0,
            'max_gross_income' => 5400000,
            'rate_percentage' => 0.0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EmployeeSalaryComponent::class)
            ->set('employee_id', $employee->id)
            ->set('salary_type', 'monthly')
            ->set('basic_salary', 5000000)
            ->set('tax_master_id', $taxMaster->id)
            ->call('save');

        $salary = EmployeeSalary::where('employee_id', $employee->id)->first();
        $this->assertNotNull($salary);
        $this->assertEquals($taxMaster->id, $salary->tax_master_id);
        $this->assertEquals('TER-A-01', $salary->taxMaster->code);

        // Test editing and unassigning
        Livewire::test(EmployeeSalaryComponent::class)
            ->call('edit', $employee->id)
            ->assertSet('tax_master_id', $taxMaster->id)
            ->set('tax_master_id', null)
            ->call('save');

        $salary->refresh();
        $this->assertNull($salary->tax_master_id);
    }
}
