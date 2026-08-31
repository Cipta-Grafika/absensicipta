<?php

namespace Tests\Feature\Payroll;

use App\Livewire\Payroll\TaxMasterComponent;
use App\Models\TaxMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxMasterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_user_and_superadmin_can_access_tax_master_page()
    {
        $payrollUser = User::factory()->create(['group' => 'payroll']);
        $superadmin = User::factory()->create(['group' => 'superadmin']);
        $regularUser = User::factory()->create(['group' => 'user']);

        $response = $this->actingAs($payrollUser)->get(route('payroll.taxes'));
        $response->assertStatus(200);

        $response = $this->actingAs($superadmin)->get(route('payroll.taxes'));
        $response->assertStatus(200);

        $response = $this->actingAs($regularUser)->get(route('payroll.taxes'));
        $response->assertStatus(403);
    }

    public function test_payroll_user_can_create_edit_and_delete_tax_master()
    {
        $admin = User::factory()->create(['group' => 'payroll']);
        $this->actingAs($admin);

        // 1. Test Create
        Livewire::test(TaxMasterComponent::class)
            ->call('openCreateModal')
            ->set('category', 'TER A')
            ->set('code', 'TER-A-99')
            ->set('min_gross_income', 5000000)
            ->set('max_gross_income', 5500000)
            ->set('rate_percentage', 0.25)
            ->set('name', 'TER A - Rp 5.000.000 s.d Rp 5.500.000 (0.25%)')
            ->set('description', 'Uji Coba')
            ->call('save')
            ->assertHasNoErrors();

        $tax = TaxMaster::where('code', 'TER-A-99')->first();
        $this->assertNotNull($tax);
        $this->assertEquals(0.25, $tax->rate_percentage);

        // 2. Test Edit
        Livewire::test(TaxMasterComponent::class)
            ->call('edit', $tax->id)
            ->assertSet('code', 'TER-A-99')
            ->set('rate_percentage', 0.50)
            ->call('save')
            ->assertHasNoErrors();

        $tax->refresh();
        $this->assertEquals(0.50, $tax->rate_percentage);

        // 3. Test Delete
        Livewire::test(TaxMasterComponent::class)
            ->call('confirmDelete', $tax->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete');

        $this->assertNull(TaxMaster::where('code', 'TER-A-99')->first());
    }

    public function test_auto_generate_name_helper()
    {
        $admin = User::factory()->create(['group' => 'payroll']);
        $this->actingAs($admin);

        Livewire::test(TaxMasterComponent::class)
            ->set('category', 'TER A')
            ->set('min_gross_income', 0)
            ->set('max_gross_income', 5400000)
            ->set('rate_percentage', 0)
            ->call('autoGenerateName')
            ->assertSet('name', 'TER A - s.d Rp 5.400.000 (0%)');
    }
}
