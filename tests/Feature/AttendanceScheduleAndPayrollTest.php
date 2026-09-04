<?php

namespace Tests\Feature;

use App\Livewire\Admin\HolidayManagementComponent;
use App\Livewire\Admin\WorkScheduleManagementComponent;
use App\Livewire\Payroll\PayrollHistoryComponent;
use App\Models\Division;
use App\Models\EmployeeSalary;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceScheduleAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_schedule_hierarchy_resolution(): void
    {
        $divisionA = Division::create(['name' => 'Division A', 'off_days' => ['saturday', 'sunday']]);
        $divisionB = Division::create(['name' => 'Division B', 'off_days' => ['friday']]);

        $user1 = User::factory()->create([
            'group' => 'user',
            'division_id' => $divisionA->id,
            'off_days' => null,
        ]);

        $user2 = User::factory()->create([
            'group' => 'user',
            'division_id' => $divisionA->id,
            'off_days' => ['monday'], // User off_days override division
        ]);

        $user3 = User::factory()->create([
            'group' => 'user',
            'division_id' => $divisionB->id,
            'off_days' => null,
        ]);

        // Dates for testing (August 2026)
        $sunday = Carbon::parse('2026-08-02'); // Sunday
        $monday = Carbon::parse('2026-08-03'); // Monday
        $tuesday = Carbon::parse('2026-08-04'); // Tuesday
        $friday = Carbon::parse('2026-08-07'); // Friday
        $saturday = Carbon::parse('2026-08-08'); // Saturday

        // 1. Default / Division off-days check
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user1, $sunday));
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user1, $saturday));
        $this->assertTrue(AttendanceScheduleService::isWorkingDay($user1, $monday));

        // 2. User off-days overriding division
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user2, $monday));
        $this->assertTrue(AttendanceScheduleService::isWorkingDay($user2, $sunday)); // User off_days is ['monday'], so Sunday is working

        // 3. General Holiday check
        $generalHoliday = Holiday::create([
            'name' => 'Independence Day',
            'date' => $tuesday->toDateString(),
            'type' => 'general',
        ]);
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user1, $tuesday));
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user3, $tuesday));

        // 4. Division Holiday check
        $divisionHoliday = Holiday::create([
            'name' => 'Div A Anniversary',
            'date' => '2026-08-05',
            'type' => 'division',
            'division_id' => $divisionA->id,
        ]);
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user1, '2026-08-05'));
        $this->assertTrue(AttendanceScheduleService::isWorkingDay($user3, '2026-08-05'));

        // 5. Custom Multi-User Holiday check
        $customHoliday = Holiday::create([
            'name' => 'Special Team Outing',
            'date' => '2026-08-06',
            'type' => 'custom',
        ]);
        $customHoliday->users()->attach([$user1->id]);
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user1, '2026-08-06'));
        $this->assertTrue(AttendanceScheduleService::isWorkingDay($user3, '2026-08-06'));

        // 6. Specific WorkSchedule Override (highest priority)
        // Mark Sunday as working day for user1 (rolling schedule)
        WorkSchedule::create([
            'date' => $sunday->toDateString(),
            'user_id' => $user1->id,
            'is_working_day' => true,
        ]);
        $this->assertTrue(AttendanceScheduleService::isWorkingDay($user1, $sunday));

        // Mark Tuesday (General Holiday) as working day via WorkSchedule override
        WorkSchedule::create([
            'date' => $tuesday->toDateString(),
            'user_id' => $user1->id,
            'is_working_day' => true,
        ]);
        $this->assertTrue(AttendanceScheduleService::isWorkingDay($user1, $tuesday));

        // Mark Tuesday as Day Off for user3 via WorkSchedule override
        WorkSchedule::create([
            'date' => '2026-08-05',
            'user_id' => $user3->id,
            'is_working_day' => false,
        ]);
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($user3, '2026-08-05'));
    }

    public function test_payroll_generation_handles_rolling_schedule_and_holidays_without_false_alfa(): void
    {
        $payrollAdmin = User::factory()->create([
            'group' => 'payroll',
        ]);

        $employee = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'salary_type' => 'monthly',
            'basic_salary' => 5000000,
            'meal_allowance' => 500000,
            'transport_allowance' => 500000,
            'attendance_allowance' => 500000,
            'working_days_per_month' => 25,
        ]);

        // WorkSchedule: Make Sunday (2026-08-02) a working day
        WorkSchedule::create([
            'date' => '2026-08-02',
            'user_id' => $employee->id,
            'is_working_day' => true,
        ]);

        // Holiday: General holiday on Monday (2026-08-03)
        Holiday::create([
            'name' => 'National Holiday',
            'date' => '2026-08-03',
            'type' => 'general',
        ]);

        // Create attendance for all working days in period (Aug 1, Aug 2, Aug 4, Aug 5, Aug 6, Aug 7)
        // Aug 3 is national holiday
        \App\Models\Attendance::create([
            'user_id' => $employee->id,
            'date' => '2026-08-01',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'user_id' => $employee->id,
            'date' => '2026-08-02',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'user_id' => $employee->id,
            'date' => '2026-08-04',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'user_id' => $employee->id,
            'date' => '2026-08-05',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'user_id' => $employee->id,
            'date' => '2026-08-06',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'user_id' => $employee->id,
            'date' => '2026-08-07',
            'status' => 'present',
        ]);

        $this->actingAs($payrollAdmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-07')
            ->call('generatePayroll');

        $payroll = Payroll::where('employee_id', $employee->id)->where('period_month', '2026-08')->first();

        $this->assertNotNull($payroll);
        // Sunday 2026-08-02 was working day and attended, Monday 2026-08-03 was holiday.
        $this->assertEquals(0, $payroll->total_absent);
        $this->assertGreaterThan(0, $payroll->net_salary);
    }

    public function test_sunday_with_work_schedule_evaluates_as_absent_when_no_record_exists(): void
    {
        $employee = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $pastSunday = Carbon::parse('2026-07-26'); // Sunday

        // Before creating WorkSchedule, Sunday is default off day -> isWorkingDay is false
        $this->assertFalse(AttendanceScheduleService::isWorkingDay($employee, $pastSunday));

        // Create WorkSchedule override: Sunday 2026-07-26 is a working day (wajib masuk)
        WorkSchedule::create([
            'date' => $pastSunday->toDateString(),
            'user_id' => $employee->id,
            'is_working_day' => true,
        ]);

        // Now isWorkingDay is true
        $this->assertTrue(AttendanceScheduleService::isWorkingDay($employee, $pastSunday));

        // Status evaluation for a past working day without attendance record must be 'absent'
        $isWorkingDay = AttendanceScheduleService::isWorkingDay($employee, $pastSunday);
        $attendance = null;
        $status = ($attendance ?? [
            'status' => !$isWorkingDay || !$pastSunday->isPast() ? '-' : 'absent',
        ])['status'];

        $this->assertEquals('absent', $status);
    }

    public function test_daily_employee_no_double_deduction(): void
    {
        $payrollAdmin = User::factory()->create([
            'group' => 'payroll',
        ]);

        $dailyEmp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        EmployeeSalary::create([
            'employee_id' => $dailyEmp->id,
            'salary_type' => 'daily',
            'basic_salary' => 150000,
            'meal_allowance' => 20000,
            'transport_allowance' => 15000,
            'attendance_allowance' => 100000,
            'working_days_per_month' => 25,
        ]);

        $this->actingAs($payrollAdmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-07')
            ->call('generatePayroll');

        $payroll = Payroll::where('employee_id', $dailyEmp->id)->first();

        $this->assertNotNull($payroll);
        // Deductions for daily employee for ALFA / Excused / Sick should be 0 (no double penalty)
        $this->assertEquals(0, $payroll->absent_deduction);
        $this->assertEquals(0, $payroll->excused_deduction);
        $this->assertEquals(0, $payroll->sick_deduction);
    }

    public function test_work_schedule_management_component_creates_and_prevents_duplicates(): void
    {
        $admin = User::factory()->create(['group' => 'admin']);
        $emp1 = User::factory()->create(['group' => 'user']);
        $emp2 = User::factory()->create(['group' => 'user']);

        $this->actingAs($admin);

        Livewire::test(WorkScheduleManagementComponent::class)
            ->set('start_date', '2026-08-10')
            ->set('end_date', '2026-08-11')
            ->set('user_ids', [$emp1->id, $emp2->id])
            ->set('is_working_day', true)
            ->set('note', 'Weekend Shift')
            ->call('create');

        $this->assertDatabaseHas('work_schedules', [
            'date' => '2026-08-10',
            'user_id' => $emp1->id,
            'is_working_day' => true,
        ]);
        $this->assertDatabaseHas('work_schedules', [
            'date' => '2026-08-11',
            'user_id' => $emp2->id,
            'is_working_day' => true,
        ]);

        // Test update/duplicate prevention
        Livewire::test(WorkScheduleManagementComponent::class)
            ->set('start_date', '2026-08-10')
            ->set('end_date', '2026-08-10')
            ->set('user_ids', [$emp1->id])
            ->set('is_working_day', false)
            ->set('note', 'Changed to Off')
            ->call('create');

        $this->assertDatabaseHas('work_schedules', [
            'date' => '2026-08-10',
            'user_id' => $emp1->id,
            'is_working_day' => false,
            'note' => 'Changed to Off',
        ]);
    }

    public function test_holiday_management_component_crud(): void
    {
        $admin = User::factory()->create(['group' => 'superadmin']);
        $division = Division::create(['name' => 'Design']);
        $emp = User::factory()->create(['group' => 'user']);

        $this->actingAs($admin);

        Livewire::test(HolidayManagementComponent::class)
            ->set('name', 'Custom Holiday')
            ->set('date', '2026-08-15')
            ->set('type', 'custom')
            ->set('user_ids', [$emp->id])
            ->call('create');

        $holiday = Holiday::where('name', 'Custom Holiday')->first();
        $this->assertNotNull($holiday);
        $this->assertTrue($holiday->users->contains($emp->id));

        // Test search filter
        Livewire::test(HolidayManagementComponent::class)
            ->set('calendar_month', '2026-08')
            ->set('search', 'Custom Holiday')
            ->assertSee('Custom Holiday');
    }

    public function test_work_schedule_management_search_filter(): void
    {
        $admin = User::factory()->create(['group' => 'admin']);
        $emp = User::factory()->create(['name' => 'Searchable Employee', 'group' => 'user']);
        
        WorkSchedule::create([
            'date' => '2026-08-20',
            'user_id' => $emp->id,
            'is_working_day' => true,
            'note' => 'UniqueNote123',
        ]);

        $this->actingAs($admin);

        Livewire::test(WorkScheduleManagementComponent::class)
            ->set('search', 'Searchable')
            ->assertSee('Searchable Employee')
            ->set('search', 'UniqueNote123')
            ->assertSee('Searchable Employee');
    }

    public function test_employee_with_full_schedule_and_leave_has_zero_absent(): void
    {
        $payrollAdmin = User::factory()->create(['group' => 'payroll']);
        $emp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'off_days' => [], // Uses roster schedules
        ]);

        EmployeeSalary::create([
            'employee_id' => $emp->id,
            'salary_type' => 'daily',
            'basic_salary' => 60000,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'working_days_per_month' => 25,
        ]);

        // OFF days matching screenshot: 01, 09, 15, 17, 23, 25, 29
        $offDays = ['2026-08-01', '2026-08-09', '2026-08-15', '2026-08-17', '2026-08-23', '2026-08-25', '2026-08-29'];
        foreach ($offDays as $offDate) {
            WorkSchedule::create([
                'user_id' => $emp->id,
                'date' => $offDate,
                'is_working_day' => false,
            ]);
        }

        // Present days matching screenshot (22 days):
        $presentDays = [
            '2026-08-02', '2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08',
            '2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13', '2026-08-14',
            '2026-08-16', '2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21', '2026-08-22',
            '2026-08-26', '2026-08-27', '2026-08-28',
            '2026-08-30'
        ];
        foreach ($presentDays as $pDate) {
            \App\Models\Attendance::create([
                'user_id' => $emp->id,
                'date' => $pDate,
                'status' => 'present',
            ]);
        }

        // Leave day on 2026-08-24
        \App\Models\Attendance::create([
            'user_id' => $emp->id,
            'date' => '2026-08-24',
            'status' => 'special-leaves',
        ]);

        // Note: 2026-08-31 has no attendance record (future / hyphen)
        Carbon::setTestNow('2026-08-25');

        $this->actingAs($payrollAdmin);

        // Generate payroll for August 2026 (1 to 31 Aug)
        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $payroll = Payroll::where('employee_id', $emp->id)->where('period_month', '2026-08')->first();

        $this->assertNotNull($payroll);
        // Total absent must be 0 (31 Aug in the future is not counted as absent)
        $this->assertEquals(0, $payroll->total_absent);
        // Total present should be 22
        $this->assertEquals(22, $payroll->total_present);
        // Effective paid days should be rounded to standard 25 days since 0 absent and >= 15 paid days
        $this->assertEquals(25 * 60000, $payroll->basic_salary_earned);
        $this->assertEquals(25 * 60000, $payroll->net_salary);

        Carbon::setTestNow();
    }

    public function test_daily_employee_with_sick_and_permit_reduces_paid_days_correctly(): void
    {
        $payrollAdmin = User::factory()->create(['group' => 'payroll']);
        $emp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'off_days' => ['sunday'],
        ]);

        EmployeeSalary::create([
            'employee_id' => $emp->id,
            'salary_type' => 'daily',
            'basic_salary' => 70000,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'working_days_per_month' => 25,
        ]);

        // Days: 22 Present, 2 Sick, 2 Excused
        // 1-22 Aug present (excluding Sundays 2, 9, 16, 23, 30)
        $presentDates = [
            '2026-08-01', '2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08',
            '2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13', '2026-08-14', '2026-08-15',
            '2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21', '2026-08-22',
            '2026-08-24', '2026-08-25', '2026-08-26'
        ];
        foreach ($presentDates as $pDate) {
            \App\Models\Attendance::create([
                'user_id' => $emp->id,
                'date' => $pDate,
                'status' => 'present',
            ]);
        }

        // 2 Sick days
        \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => '2026-08-27', 'status' => 'sick']);
        \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => '2026-08-28', 'status' => 'sick']);

        // 2 Excused days
        \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => '2026-08-29', 'status' => 'excused']);
        \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => '2026-08-31', 'status' => 'excused']);

        $this->actingAs($payrollAdmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $payroll = Payroll::where('employee_id', $emp->id)->where('period_month', '2026-08')->first();

        $this->assertNotNull($payroll);
        $this->assertEquals(0, $payroll->total_absent);
        $this->assertEquals(2, $payroll->total_sick);
        $this->assertEquals(2, $payroll->total_excused);
        $this->assertEquals(22, $payroll->total_present);

        // Effective paid days = 25 - (0 Alpa + 2 Sick + 2 Excused) = 21 days!
        // Basic salary earned = 21 * 70.000 = 1.470.000
        $this->assertEquals(21 * 70000, $payroll->basic_salary_earned);
        $this->assertEquals(21 * 70000, $payroll->net_salary);
        $this->assertEquals(0, $payroll->total_deduction);
    }

    public function test_special_leave_consecutive_days_greater_than_two_deducts_correctly(): void
    {
        $payrollAdmin = User::factory()->create(['group' => 'payroll']);
        $emp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'off_days' => ['sunday'],
        ]);

        EmployeeSalary::create([
            'employee_id' => $emp->id,
            'salary_type' => 'monthly',
            'basic_salary' => 2500000,
            'meal_allowance' => 0,
            'transport_allowance' => 500000,
            'attendance_allowance' => 1100000,
            'working_days_per_month' => 25,
        ]);

        // 3 consecutive special-leaves (days 1, 3, 4 of August - since 2 is Sunday)
        \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => '2026-08-01', 'status' => 'special-leaves']);
        \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => '2026-08-03', 'status' => 'special-leaves']);
        \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => '2026-08-04', 'status' => 'special-leaves']); // 3rd consecutive -> penalized

        // Rest of August (5 to 31 except Sundays) present
        $dates = [
            '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08',
            '2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13', '2026-08-14', '2026-08-15',
            '2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21', '2026-08-22',
            '2026-08-24', '2026-08-25', '2026-08-26', '2026-08-27', '2026-08-28', '2026-08-29',
            '2026-08-31'
        ];
        foreach ($dates as $pDate) {
            \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => $pDate, 'status' => 'present']);
        }

        $this->actingAs($payrollAdmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $payroll = Payroll::where('employee_id', $emp->id)->where('period_month', '2026-08')->first();

        $this->assertNotNull($payroll);
        $this->assertEquals(1, $payroll->penalized_cuti_days);
        $this->assertEquals(0, $payroll->total_absent);

        // Expected cuti deduction: (1 / 25) * (500000 + 1100000) = 64000
        $expectedDeduction = (int) round((1 / 25) * (500000 + 1100000));
        $this->assertEquals(64000, $expectedDeduction);
        $this->assertEquals($expectedDeduction, $payroll->total_deduction);
        $this->assertEquals(4100000 - 64000, $payroll->net_salary);
    }

    public function test_intern_part_time_and_pkl_calculates_pure_daily_attendance_earnings(): void
    {
        $payrollAdmin = User::factory()->create(['group' => 'payroll']);
        
        // 1. Intern with 24 days actual presence
        $intern = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'type' => 'intern', // magang
            'off_days' => ['sunday'],
        ]);

        EmployeeSalary::create([
            'employee_id' => $intern->id,
            'salary_type' => 'daily',
            'basic_salary' => 60000,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'working_days_per_month' => 25,
        ]);

        // Create exactly 24 present attendance records
        $dates24 = [
            '2026-08-01', '2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08',
            '2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13', '2026-08-14', '2026-08-15',
            '2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21', '2026-08-22',
            '2026-08-24', '2026-08-25', '2026-08-26', '2026-08-27', '2026-08-28'
        ];
        foreach ($dates24 as $d) {
            \App\Models\Attendance::create(['user_id' => $intern->id, 'date' => $d, 'status' => 'present']);
        }

        $this->actingAs($payrollAdmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $payroll = Payroll::where('employee_id', $intern->id)->where('period_month', '2026-08')->first();

        $this->assertNotNull($payroll);
        $this->assertEquals(24, $payroll->total_present);
        
        // Exact pure daily calculation: 24 days * 60.000 = 1.440.000 (NOT rounded to 25)
        $this->assertEquals(24 * 60000, $payroll->basic_salary_earned);
        $this->assertEquals(24 * 60000, $payroll->net_salary);
        $this->assertEquals(0, $payroll->total_deduction);
    }

    public function test_bpjs_and_pph21_deductions_calculated_and_recorded_in_payroll(): void
    {
        $payrollAdmin = User::factory()->create(['group' => 'payroll']);
        $emp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'off_days' => ['sunday'],
        ]);

        $taxTier = \App\Models\TaxMaster::create([
            'category' => 'TER A',
            'code' => 'TER_A_02',
            'name' => 'TER A Tier 2 (0.25%)',
            'min_gross_income' => 5400001,
            'max_gross_income' => 5650000,
            'rate_percentage' => 0.25,
        ]);

        EmployeeSalary::create([
            'employee_id' => $emp->id,
            'salary_type' => 'monthly',
            'basic_salary' => 4000000,
            'meal_allowance' => 500000,
            'transport_allowance' => 500000,
            'attendance_allowance' => 500000, // Total gross = 5.500.000 (fits in Tier 2: 0.25%)
            'working_days_per_month' => 25,
            'bpjs' => 150000,
            'tax_master_id' => $taxTier->id,
        ]);

        // Present all working days of August 2026 (26 days)
        $dates = [
            '2026-08-01', '2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08',
            '2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13', '2026-08-14', '2026-08-15',
            '2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21', '2026-08-22',
            '2026-08-24', '2026-08-25', '2026-08-26', '2026-08-27', '2026-08-28', '2026-08-29',
            '2026-08-31'
        ];
        foreach ($dates as $pDate) {
            \App\Models\Attendance::create(['user_id' => $emp->id, 'date' => $pDate, 'status' => 'present']);
        }

        $this->actingAs($payrollAdmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $payroll = Payroll::with('details')->where('employee_id', $emp->id)->where('period_month', '2026-08')->first();

        $this->assertNotNull($payroll);
        // Gross income = 4.000.000 + 1.500.000 = 5.500.000
        $gross = 5500000;
        // PPh 21 = 0.25% * 5.500.000 = 13.750
        $expectedPph = (int) round((0.25 / 100) * $gross); // 13750
        $expectedBpjs = 150000;
        $expectedTotalDeduction = $expectedBpjs + $expectedPph; // 163750

        $this->assertEquals($expectedTotalDeduction, $payroll->total_deduction);
        $this->assertEquals($gross - $expectedTotalDeduction, $payroll->net_salary);

        // Check details table
        $bpjsDetail = $payroll->details->where('type', 'deduction')->filter(fn($d) => str_contains($d->name, 'BPJS'))->first();
        $this->assertNotNull($bpjsDetail);
        $this->assertEquals($expectedBpjs, $bpjsDetail->amount);

        $pphDetail = $payroll->details->where('type', 'deduction')->filter(fn($d) => str_contains($d->name, 'PPh 21'))->first();
        $this->assertNotNull($pphDetail);
        $this->assertEquals($expectedPph, $pphDetail->amount);

        // Test HTML Payslip rendering contains real deduction values
        $html = view('user.payslip-print', ['payroll' => $payroll])->render();
        $this->assertStringContainsString('Potongan', $html);
        $this->assertStringContainsString(number_format($expectedBpjs, 0, ',', ','), $html);
        $this->assertStringContainsString(number_format($expectedPph, 0, ',', ','), $html);

        // Mark as paid so employee can print
        $payroll->update(['status' => 'paid', 'payment_date' => '2026-08-31']);

        // Test Print / PDF download response
        $this->actingAs($emp);
        $response = $this->get(route('user.payslip.print', $payroll->id));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_payroll_user_can_download_draft_payslip_but_employee_requires_paid_status(): void
    {
        $payrollAdmin = User::factory()->create(['group' => 'payroll']);
        $emp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'birth_date' => '1995-08-25',
        ]);

        $payroll = Payroll::create([
            'employee_id' => $emp->id,
            'period_month' => '2026-08',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'basic_salary_earned' => 3000000,
            'total_allowance' => 500000,
            'total_overtime_pay' => 0,
            'total_deduction' => 0,
            'net_salary' => 3500000,
            'status' => 'draft', // DRAFT status!
        ]);

        // 1. Payroll admin can download draft payslip via payroll.payslip.print
        $this->actingAs($payrollAdmin);
        $resPayroll = $this->get(route('payroll.payslip.print', $payroll->id));
        $resPayroll->assertStatus(200);
        $resPayroll->assertHeader('Content-Type', 'application/pdf');

        // 2. Regular employee CANNOT download draft payslip (404/not found because not paid yet)
        $this->actingAs($emp);
        $resEmpDraft = $this->get(route('user.payslip.print', $payroll->id));
        $resEmpDraft->assertStatus(404);

        // 3. Once marked as paid, employee CAN download it
        $payroll->update(['status' => 'paid', 'payment_date' => now()]);
        $resEmpPaid = $this->get(route('user.payslip.print', $payroll->id));
        $resEmpPaid->assertStatus(200);
        $resEmpPaid->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cipta_food_division_employee_has_flat_10000_late_deduction_per_day(): void
    {
        $payrollAdmin = User::factory()->create(['group' => 'payroll']);
        
        $ciptaFoodDivision = \App\Models\Division::create(['name' => 'Cipta Food']);
        $otherDivision = \App\Models\Division::create(['name' => 'Percetakan']);

        $shift = \App\Models\Shift::create([
            'name' => 'Shift Food',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $foodEmp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'division_id' => $ciptaFoodDivision->id,
            'off_days' => ['sunday'],
        ]);

        $otherEmp = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
            'division_id' => $otherDivision->id,
            'off_days' => ['sunday'],
        ]);

        // Monthly salary with Rp 300/min late deduction rate
        EmployeeSalary::create([
            'employee_id' => $foodEmp->id,
            'salary_type' => 'monthly',
            'basic_salary' => 3000000,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'late_deduction_rate' => 300, // 300 per minute
            'working_days_per_month' => 25,
        ]);

        EmployeeSalary::create([
            'employee_id' => $otherEmp->id,
            'salary_type' => 'monthly',
            'basic_salary' => 3000000,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'late_deduction_rate' => 300, // 300 per minute
            'working_days_per_month' => 25,
        ]);

        // 25 working days. Both employees are late on 2 days (day 1: 30 min, day 2: 30 min => total 60 min over 2 days)
        for ($day = 1; $day <= 31; $day++) {
            $dateStr = sprintf('2026-08-%02d', $day);
            $isWork = ($day <= 25);
            WorkSchedule::create(['user_id' => $foodEmp->id, 'date' => $dateStr, 'is_working_day' => $isWork]);
            WorkSchedule::create(['user_id' => $otherEmp->id, 'date' => $dateStr, 'is_working_day' => $isWork]);

            if ($isWork) {
                $isLate = in_array($day, [1, 2]);
                $timeIn = $isLate ? '08:30:00' : '08:00:00';
                $status = $isLate ? 'late' : 'present';

                \App\Models\Attendance::create([
                    'user_id' => $foodEmp->id,
                    'shift_id' => $shift->id,
                    'date' => $dateStr,
                    'time_in' => $timeIn,
                    'time_out' => '17:00:00',
                    'status' => $status,
                ]);

                \App\Models\Attendance::create([
                    'user_id' => $otherEmp->id,
                    'shift_id' => $shift->id,
                    'date' => $dateStr,
                    'time_in' => $timeIn,
                    'time_out' => '17:00:00',
                    'status' => $status,
                ]);
            }
        }

        $this->actingAs($payrollAdmin);

        Livewire::test(PayrollHistoryComponent::class)
            ->set('generate_period_month', '2026-08')
            ->set('generate_start_date', '2026-08-01')
            ->set('generate_end_date', '2026-08-31')
            ->call('generatePayroll');

        $foodPayroll = Payroll::where('employee_id', $foodEmp->id)->where('period_month', '2026-08')->first();
        $otherPayroll = Payroll::where('employee_id', $otherEmp->id)->where('period_month', '2026-08')->first();

        $this->assertNotNull($foodPayroll);
        $this->assertNotNull($otherPayroll);

        // Food employee: 2 late days = 2 * Rp 10.000 = Rp 20.000 deduction
        $this->assertEquals(20000, (int) $foodPayroll->total_deduction);
        $this->assertEquals(2980000, (int) $foodPayroll->net_salary);
        $lateDetailFood = $foodPayroll->details->where('type', 'deduction')->first();
        $this->assertStringContainsString('2 Hari @ Rp 10.000', $lateDetailFood->name);
        $this->assertEquals(20000, (int) $lateDetailFood->amount);

        // Other employee: 60 minutes * Rp 300 = Rp 18.000 deduction
        $this->assertEquals(18000, (int) $otherPayroll->total_deduction);
        $this->assertEquals(2982000, (int) $otherPayroll->net_salary);
        $lateDetailOther = $otherPayroll->details->where('type', 'deduction')->first();
        $this->assertStringContainsString('60 Menit', $lateDetailOther->name);
        $this->assertEquals(18000, (int) $lateDetailOther->amount);
    }
}
