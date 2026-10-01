<?php

namespace Tests\Feature;

use App\Livewire\Admin\AttendanceComponent;
use App\Livewire\Admin\EmployeeComponent;
use App\Livewire\Payroll\PayrollHistoryComponent;
use App\Livewire\Payroll\SavingTransactionComponent;
use App\Livewire\ScanComponent;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NonAttendanceMemberSyirkahTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $syirkahAdmin;
    private Division $division;
    private JobTitle $jobTitle;
    private Saving $saving;

    protected function setUp(): void
    {
        parent::setUp();

        $this->division = Division::create(['name' => 'Divisi Digital']);
        $this->jobTitle = JobTitle::create(['name' => 'Designer', 'division_id' => $this->division->id]);

        $this->superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'group' => 'superadmin',
            'division_id' => $this->division->id,
            'is_attendance_required' => true,
        ]);

        $this->syirkahAdmin = User::factory()->create([
            'name' => 'Admin Syirkah',
            'group' => 'syirkah',
            'division_id' => $this->division->id,
            'is_attendance_required' => true,
        ]);

        $this->saving = Saving::create([
            'savings_name' => 'Syirkah Modal',
            'mandatory_amount' => 50000,
            'secondary_amount' => 10000,
            'is_active' => true,
        ]);
    }

    public function test_existing_and_new_users_have_is_attendance_required_true_by_default()
    {
        $user = User::factory()->create([
            'name' => 'Default Employee',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $this->division->id,
        ]);

        $this->assertTrue($user->is_attendance_required);
    }

    public function test_superadmin_can_create_non_attendance_user()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(EmployeeComponent::class)
            ->call('showCreating')
            ->set('form.name', 'Non Attendance Member')
            ->set('form.email', 'nonabsen@example.com')
            ->set('form.phone', '08123456789')
            ->set('form.gender', 'male')
            ->set('form.city', 'Jakarta')
            ->set('form.address', 'Jl. Merdeka')
            ->set('form.division_id', $this->division->id)
            ->set('form.job_title_id', $this->jobTitle->id)
            ->set('form.is_attendance_required', false)
            ->call('create');

        $user = User::where('email', 'nonabsen@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_attendance_required);
    }

    public function test_non_attendance_user_is_excluded_from_attendance_list()
    {
        $attendanceUser = User::factory()->create([
            'name' => 'Karyawan Absen',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $this->division->id,
            'is_attendance_required' => true,
        ]);

        $nonAttendanceUser = User::factory()->create([
            'name' => 'Karyawan Non Absen',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $this->division->id,
            'is_attendance_required' => false,
        ]);

        $this->actingAs($this->superadmin);

        Livewire::test(AttendanceComponent::class)
            ->assertSee($attendanceUser->name)
            ->assertDontSee($nonAttendanceUser->name);
    }

    public function test_non_attendance_user_cannot_scan_check_in()
    {
        $nonAttendanceUser = User::factory()->create([
            'name' => 'Karyawan Non Absen',
            'nip' => 'EMP-NONABSEN-001',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $this->division->id,
            'is_attendance_required' => false,
        ]);

        $this->actingAs($nonAttendanceUser);

        $result = Livewire::test(ScanComponent::class)
            ->call('scan', 'ANY_BARCODE');

        $this->assertEquals('Absen Tidak Diperlukan: Akun Anda terdaftar sebagai anggota non-absensi.', $result->instance()->scan('ANY_BARCODE'));
    }

    public function test_non_attendance_user_is_excluded_from_payroll_generation()
    {
        $attendanceUser = User::factory()->create([
            'name' => 'Karyawan Absen',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $this->division->id,
            'is_attendance_required' => true,
        ]);

        \App\Models\EmployeeSalary::create([
            'employee_id' => $attendanceUser->id,
            'salary_type' => 'monthly',
            'basic_salary' => 5000000,
            'working_days_per_month' => 25,
            'annual_leave_quota' => 12,
        ]);

        $nonAttendanceUser = User::factory()->create([
            'name' => 'Karyawan Non Absen',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $this->division->id,
            'is_attendance_required' => false,
        ]);

        $this->actingAs($this->superadmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('month', '2026-10')
            ->call('selectAllAvailableEmployees')
            ->assertSet('selected_employee_ids', [(string) $attendanceUser->id])
            ->assertViewHas('availableEmployees', function ($employees) use ($attendanceUser, $nonAttendanceUser) {
                return $employees->contains('id', $attendanceUser->id) &&
                    !$employees->contains('id', $nonAttendanceUser->id);
            });
    }

    public function test_non_attendance_user_can_receive_direct_deposit_and_edit_mutation()
    {
        $nonAttendanceUser = User::factory()->create([
            'name' => 'Anggota Syirkah Murni',
            'nip' => 'SYIRKAH-001',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $this->division->id,
            'is_attendance_required' => false,
        ]);

        $this->actingAs($this->syirkahAdmin);

        // 1. Direct Deposit (Setoran Langsung)
        Livewire::test(SavingTransactionComponent::class)
            ->call('openDepositModal')
            ->set('deposit_user_id', $nonAttendanceUser->id)
            ->set('deposit_savings_id', $this->saving->id)
            ->set('deposit_mandatory_amount', 250000)
            ->set('deposit_secondary_amount', 100000)
            ->set('deposit_description', 'Setoran Awal Syirkah Non-Absen')
            ->call('processDeposit')
            ->assertHasNoErrors();

        $transaction = SavingTransaction::where('user_id', $nonAttendanceUser->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(250000, $transaction->mandatory_amount);
        $this->assertEquals(100000, $transaction->secondary_amount);
        $this->assertEquals('Setoran Awal Syirkah Non-Absen', $transaction->description);
        $this->assertEquals('approved', $transaction->status);

        // Check running balance in SavingSummary
        $summary = \App\Models\SavingSummary::where('user_id', $nonAttendanceUser->id)
            ->where('savings_id', $this->saving->id)
            ->first();
        $this->assertNotNull($summary);
        $this->assertEquals(250000, $summary->total_mandatory);
        $this->assertEquals(100000, $summary->total_secondary);

        // 2. Edit Mutation (Edit Nominal & Keterangan)
        Livewire::test(SavingTransactionComponent::class)
            ->call('openEditNominalModal', $transaction->id)
            ->set('edit_mandatory_amount', 300000)
            ->set('edit_secondary_amount', 150000)
            ->set('edit_description', 'Revisi Setoran Awal Syirkah Non-Absen')
            ->call('updateNominal')
            ->assertHasNoErrors();

        $transaction->refresh();
        $this->assertEquals(300000, $transaction->mandatory_amount);
        $this->assertEquals(150000, $transaction->secondary_amount);
        $this->assertEquals('Revisi Setoran Awal Syirkah Non-Absen', $transaction->description);

        $summary->refresh();
        $this->assertEquals(300000, $summary->total_mandatory);
        $this->assertEquals(150000, $summary->total_secondary);
    }
}
