<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EmployeeComponent;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeWarningLetterTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $employee;
    protected Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->division = Division::factory()->create([
            'name' => 'IT Development',
        ]);

        $this->superAdmin = User::factory()->create([
            'group' => 'superadmin',
            'division_id' => $this->division->id,
        ]);

        $this->admin = User::factory()->create([
            'group' => 'admin',
            'division_id' => $this->division->id,
        ]);

        $this->employee = User::factory()->create([
            'group' => 'user',
            'division_id' => $this->division->id,
            'status' => 'active',
            'splt' => false,
            'sp1' => false,
            'sp2' => false,
            'sp3' => false,
            'cutoff' => false,
        ]);
    }

    public function test_user_has_default_false_warning_letters_and_accessors()
    {
        $this->assertFalse($this->employee->splt);
        $this->assertFalse($this->employee->sp1);
        $this->assertFalse($this->employee->sp2);
        $this->assertFalse($this->employee->sp3);
        $this->assertFalse($this->employee->cutoff);
        $this->assertFalse($this->employee->has_warning_letter);
        $this->assertEmpty($this->employee->active_warning_letters);
        $this->assertNull($this->employee->highest_warning_letter);

        $this->employee->update(['sp1' => true]);
        $this->employee->refresh();

        $this->assertTrue($this->employee->has_warning_letter);
        $this->assertContains('SP 1', $this->employee->active_warning_letters);
        $this->assertEquals('SP 1', $this->employee->highest_warning_letter);
    }

    public function test_employees_table_shows_warning_letters_and_no_city_column()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('hr.employees'));

        $response->assertStatus(200);
        $response->assertSee('Surat Peringatan');
        $response->assertSee('SPLT');
        $response->assertSee('SP 1');
        $response->assertSee('SP 2');
        $response->assertSee('SP 3');
        $response->assertSee('CUT OFF');

        // Check that table header does not contain City/Kota column
        Livewire::test(EmployeeComponent::class)
            ->assertSeeHtml('Surat Peringatan')
            ->assertSeeHtml('SPLT')
            ->assertSeeHtml('SP 1')
            ->assertSeeHtml('SP 2')
            ->assertSeeHtml('SP 3')
            ->assertSeeHtml('CUT OFF')
            ->assertDontSeeHtml('scope="col" class="hidden px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 sm:table-cell"');
    }

    public function test_superadmin_can_toggle_warning_letter_via_livewire()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(EmployeeComponent::class)
            ->call('toggleWarningLetter', $this->employee->id, 'splt')
            ->assertHasNoErrors();

        $this->employee->refresh();
        $this->assertTrue($this->employee->splt);

        Livewire::test(EmployeeComponent::class)
            ->call('toggleWarningLetter', $this->employee->id, 'splt')
            ->assertHasNoErrors();

        $this->employee->refresh();
        $this->assertFalse($this->employee->splt);
    }

    public function test_admin_cannot_toggle_warning_letter_via_livewire()
    {
        $this->actingAs($this->admin);

        Livewire::test(EmployeeComponent::class)
            ->call('toggleWarningLetter', $this->employee->id, 'sp1')
            ->assertForbidden();

        $this->employee->refresh();
        $this->assertFalse($this->employee->sp1);
    }

    public function test_superadmin_can_update_warning_letter_via_patch_api()
    {
        $response = $this->actingAs($this->superAdmin)->patchJson(
            route('hr.employees.update-warning-letter', $this->employee->id),
            [
                'field' => 'sp2',
                'value' => true,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'employee' => [
                'id' => $this->employee->id,
                'field' => 'sp2',
                'value' => true,
                'sp2' => true,
            ],
        ]);

        $this->employee->refresh();
        $this->assertTrue($this->employee->sp2);
    }

    public function test_admin_cannot_update_warning_letter_via_patch_api()
    {
        $response = $this->actingAs($this->admin)->patchJson(
            route('hr.employees.update-warning-letter', $this->employee->id),
            [
                'field' => 'sp2',
                'value' => true,
            ]
        );

        $response->assertStatus(403);

        $this->employee->refresh();
        $this->assertFalse($this->employee->sp2);
    }

    public function test_employee_home_displays_warning_alert_and_legend()
    {
        // When no warning is active
        $response = $this->actingAs($this->employee)->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Status Surat Peringatan');
        $response->assertSee('Bebas Peringatan');
        $response->assertSee('SPLT = Surat Peringatan Lisan Tertulis');
        $response->assertSee('SP = Surat Peringatan');

        // When a warning is active (e.g. SP 1)
        $this->employee->update(['sp1' => true]);
        $responseWithWarning = $this->actingAs($this->employee)->get(route('home'));
        $responseWithWarning->assertStatus(200);
        $responseWithWarning->assertSee('SP 1 Aktif');
        $responseWithWarning->assertSee('Detail Surat Peringatan');
        $responseWithWarning->assertSee('Cut Off (Pemutusan Hubungan Kerja)');
    }
}
