<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EmployeeComponent;
use App\Livewire\Forms\UserForm;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeDeletionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Division $divisionA;
    private Division $divisionB;
    private User $superadmin;
    private User $admin;
    private User $employeeInDivA;
    private User $employeeInDivB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->divisionA = Division::create(['name' => 'Divisi Percetakan']);
        $this->divisionB = Division::create(['name' => 'Divisi Marketing']);

        $this->superadmin = User::factory()->create([
            'name' => 'Super Administrator',
            'group' => 'superadmin',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Divisi Percetakan',
            'group' => 'admin',
            'division_id' => $this->divisionA->id,
            'status' => 'active',
        ]);

        $this->employeeInDivA = User::factory()->create([
            'name' => 'Karyawan Percetakan',
            'group' => 'user',
            'division_id' => $this->divisionA->id,
            'status' => 'active',
        ]);

        $this->employeeInDivB = User::factory()->create([
            'name' => 'Karyawan Marketing',
            'group' => 'user',
            'division_id' => $this->divisionB->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_and_superadmin_can_access_employees_page()
    {
        $response = $this->actingAs($this->admin)->get(route('hr.employees'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->superadmin)->get(route('hr.employees'));
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_employees_page()
    {
        $response = $this->actingAs($this->employeeInDivA)->get(route('hr.employees'));
        $response->assertRedirect(route('home'));
    }

    public function test_admin_does_not_see_delete_button_on_employees_component()
    {
        Livewire::actingAs($this->admin)
            ->test(EmployeeComponent::class)
            ->assertSee($this->employeeInDivA->name)
            ->assertDontSee('title="Hapus Karyawan"', false)
            ->assertDontSee('confirmDeletion');
    }

    public function test_superadmin_sees_delete_button_on_employees_component()
    {
        Livewire::actingAs($this->superadmin)
            ->test(EmployeeComponent::class)
            ->assertSee($this->employeeInDivA->name)
            ->assertSee('title="Hapus Karyawan"', false);
    }

    public function test_admin_cannot_trigger_confirm_deletion()
    {
        Livewire::actingAs($this->admin)
            ->test(EmployeeComponent::class)
            ->call('confirmDeletion', $this->employeeInDivA->id, $this->employeeInDivA->name)
            ->assertForbidden();
    }

    public function test_admin_cannot_trigger_delete()
    {
        Livewire::actingAs($this->admin)
            ->test(EmployeeComponent::class)
            ->set('selectedId', $this->employeeInDivA->id)
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $this->employeeInDivA->id,
        ]);
    }

    public function test_superadmin_can_delete_employee_via_employee_component()
    {
        Livewire::actingAs($this->superadmin)
            ->test(EmployeeComponent::class)
            ->call('confirmDeletion', $this->employeeInDivA->id, $this->employeeInDivA->name)
            ->assertSet('confirmingDeletion', true)
            ->assertSet('selectedId', $this->employeeInDivA->id)
            ->call('delete')
            ->assertSet('confirmingDeletion', false);

        $this->assertDatabaseMissing('users', [
            'id' => $this->employeeInDivA->id,
        ]);
    }

    public function test_admin_calling_user_form_delete_directly_is_forbidden()
    {
        $this->actingAs($this->admin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Akses Ditolak: Hanya Superadmin yang memiliki wewenang untuk menghapus pengguna.');

        $form = new UserForm(new EmployeeComponent(), 'form');
        $form->setUser($this->employeeInDivA);
        $form->delete();
    }

    public function test_admin_can_still_create_and_update_employee_in_own_division()
    {
        // Admin can edit employee in same division
        Livewire::actingAs($this->admin)
            ->test(EmployeeComponent::class)
            ->call('edit', $this->employeeInDivA->id)
            ->assertSet('editing', true)
            ->set('form.name', 'Karyawan Percetakan Updated')
            ->call('update')
            ->assertSet('editing', false);

        $this->assertDatabaseHas('users', [
            'id' => $this->employeeInDivA->id,
            'name' => 'Karyawan Percetakan Updated',
        ]);
    }
}
