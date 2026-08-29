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
}
