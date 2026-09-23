<?php

namespace Tests\Feature;

use App\Models\OvertimeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeMealAllowanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_meal_allowance_awarded_only_within_designated_start_window(): void
    {
        // Setup Rate: Tier 1 (1-3 jam, rate 15.000, meal allowance 20.000 with window 17:00 - 18:00)
        $r1 = OvertimeRate::create([
            'name' => 'Lembur 1-3 Jam',
            'min_hours' => 1,
            'max_hours' => 3,
            'rate_amount' => 15000,
            'rate_type' => 'per_hour',
            'division_id' => null,
            'employee_type' => 'all',
            'meal_allowance' => 20000,
            'meal_min_start_time' => '17:00',
            'meal_max_start_time' => '18:00',
            'meal_condition_type' => 'start_time_gte',
        ]);

        // Setup Rate: Tier 2 (3-24 jam, rate 20.000, no meal allowance)
        $r2 = OvertimeRate::create([
            'name' => 'Lembur 4-24 Jam',
            'min_hours' => 3,
            'max_hours' => 24,
            'rate_amount' => 20000,
            'rate_type' => 'per_hour',
            'division_id' => null,
            'employee_type' => 'all',
            'meal_allowance' => 0,
            'meal_min_start_time' => null,
            'meal_max_start_time' => null,
            'meal_condition_type' => 'start_time_gte',
        ]);

        $user = User::factory()->create(['group' => 'user']);

        // Case 1: Start at 17:00 (Eligible) -> 3h: 3*15.000 + 20.000 = 65.000
        $calc1 = OvertimeRate::calculatePayForDuration(3.0, $user, '17:00:00', '20:30:00', '2026-08-27');
        $this->assertEquals(20000, $calc1['meal_allowance']);
        $this->assertEquals(65000, $calc1['total_pay']);

        // Case 2: Start at 18:00 (Eligible) -> 6h: 3*15.000 + 3*20.000 + 20.000 = 125.000
        $calc2 = OvertimeRate::calculatePayForDuration(6.0, $user, '18:00:00', '00:00:00', '2026-08-25');
        $this->assertEquals(20000, $calc2['meal_allowance']);
        $this->assertEquals(125000, $calc2['total_pay']);

        // Case 3: Start at 17:30 (Eligible) -> 4h: 3*15.000 + 1*20.000 + 20.000 = 85.000
        $calc3 = OvertimeRate::calculatePayForDuration(4.0, $user, '17:30:00', '21:30:00', '2026-08-25');
        $this->assertEquals(20000, $calc3['meal_allowance']);
        $this->assertEquals(85000, $calc3['total_pay']);

        // Case 4: Start at 19:00 (NOT Eligible - Outside 17:00 - 18:00 window) -> 4h: 3*15.000 + 1*20.000 = 65.000 (meal = 0)
        $calc4 = OvertimeRate::calculatePayForDuration(4.0, $user, '19:00:00', '23:00:00', '2026-08-11');
        $this->assertEquals(0, $calc4['meal_allowance']);
        $this->assertEquals(65000, $calc4['total_pay']);
    }

    public function test_multi_window_lunch_and_dinner_meal_allowances_accumulate_accurately(): void
    {
        // Rate 1: Evening meal rule (17:00 - 18:00, 20.000)
        OvertimeRate::create([
            'name' => 'Lembur Malam 1-3 Jam',
            'min_hours' => 1,
            'max_hours' => 3,
            'rate_amount' => 15000,
            'rate_type' => 'per_hour',
            'division_id' => null,
            'employee_type' => 'all',
            'meal_allowance' => 20000,
            'meal_min_start_time' => '17:00',
            'meal_max_start_time' => '18:00',
            'meal_condition_type' => 'start_time_gte',
        ]);

        // Rate 2: Lunch meal rule (12:00 - 13:00, 20.000, crosses_time)
        OvertimeRate::create([
            'name' => 'Lembur Siang 1-3 Jam',
            'min_hours' => 1,
            'max_hours' => 3,
            'rate_amount' => 15000,
            'rate_type' => 'per_hour',
            'division_id' => null,
            'employee_type' => 'all',
            'meal_allowance' => 20000,
            'meal_min_start_time' => '12:00',
            'meal_max_start_time' => '13:00',
            'meal_condition_type' => 'crosses_time',
        ]);

        // Rate 3: Subsequent hours tier
        OvertimeRate::create([
            'name' => 'Lembur > 3 Jam',
            'min_hours' => 3,
            'max_hours' => 24,
            'rate_amount' => 20000,
            'rate_type' => 'per_hour',
            'division_id' => null,
            'employee_type' => 'all',
            'meal_allowance' => 0,
        ]);

        $user = User::factory()->create(['group' => 'user', 'type' => 'full-time']);

        // 1. Daytime overtime crossing lunchtime (10:00 - 14:00 = 4 Jam)
        // Hourly: 3*15.000 + 1*20.000 = 65.000
        // Meal: Lunch (20.000), Dinner (0) = 20.000
        // Total: 85.000
        $lunchCalc = OvertimeRate::calculatePayForDuration(4.0, $user, '10:00:00', '14:00:00', '2026-09-23');
        $this->assertEquals(20000, $lunchCalc['meal_allowance']);
        $this->assertCount(1, $lunchCalc['meal_details']);
        $this->assertEquals('Uang Makan Siang (12:00 - 13:00)', $lunchCalc['meal_details'][0]['name']);
        $this->assertEquals(85000, $lunchCalc['total_pay']);

        // 2. Evening overtime (17:00 - 20:00 = 3 Jam)
        // Hourly: 3*15.000 = 45.000
        // Meal: Lunch (0), Dinner (20.000) = 20.000
        // Total: 65.000
        $dinnerCalc = OvertimeRate::calculatePayForDuration(3.0, $user, '17:00:00', '20:00:00', '2026-09-23');
        $this->assertEquals(20000, $dinnerCalc['meal_allowance']);
        $this->assertCount(1, $dinnerCalc['meal_details']);
        $this->assertEquals('Uang Makan Malam (17:00 - 18:00)', $dinnerCalc['meal_details'][0]['name']);
        $this->assertEquals(65000, $dinnerCalc['total_pay']);

        // 3. Full-day overtime crossing both lunch and dinner (10:00 - 20:00 = 10 Jam)
        // Hourly: 3*15.000 + 7*20.000 = 185.000
        // Meal: Lunch (20.000) + Dinner (20.000) = 40.000!
        // Total: 185.000 + 40.000 = 225.000
        $fullDayCalc = OvertimeRate::calculatePayForDuration(10.0, $user, '10:00:00', '20:00:00', '2026-09-23');
        $this->assertEquals(40000, $fullDayCalc['meal_allowance']);
        $this->assertCount(2, $fullDayCalc['meal_details']);
        $this->assertEquals(185000, $fullDayCalc['total_hourly_pay']);
        $this->assertEquals(225000, $fullDayCalc['total_pay']);

        // 4. Morning overtime outside meal windows (08:00 - 11:30 = 3.5 Jam)
        // Hourly: 3*15.000 + 0.5*20.000 = 55.000
        // Meal: 0
        // Total: 55.000
        $morningCalc = OvertimeRate::calculatePayForDuration(3.5, $user, '08:00:00', '11:30:00', '2026-09-23');
        $this->assertEquals(0, $morningCalc['meal_allowance']);
        $this->assertCount(0, $morningCalc['meal_details']);
        $this->assertEquals(55000, $morningCalc['total_pay']);
    }

    public function test_tier_rate_deduplication_prevents_double_billing(): void
    {
        // Setup TWO rates for the exact same tier 1-3 Jam to represent different meal windows
        OvertimeRate::create([
            'name' => '1-3 Jam (Malam)',
            'min_hours' => 1,
            'max_hours' => 3,
            'rate_amount' => 15000,
            'rate_type' => 'per_hour',
            'division_id' => null,
            'employee_type' => 'full-time',
            'meal_allowance' => 20000,
            'meal_min_start_time' => '17:00',
            'meal_max_start_time' => '18:00',
        ]);

        OvertimeRate::create([
            'name' => '1-3 Jam (Siang)',
            'min_hours' => 1,
            'max_hours' => 3,
            'rate_amount' => 15000,
            'rate_type' => 'per_hour',
            'division_id' => null,
            'employee_type' => 'full-time',
            'meal_allowance' => 20000,
            'meal_min_start_time' => '12:00',
            'meal_max_start_time' => '13:00',
            'meal_condition_type' => 'crosses_time',
        ]);

        $user = User::factory()->create(['group' => 'user', 'type' => 'full-time']);

        // For a 3-hour overtime, hourly pay MUST be exactly 3 * 15.000 = 45.000, NOT doubled to 90.000
        $calc = OvertimeRate::calculatePayForDuration(3.0, $user, '17:00:00', '20:00:00', '2026-09-23');
        $this->assertEquals(45000, $calc['total_hourly_pay']);
        $this->assertCount(1, $calc['breakdown']);
        $this->assertEquals(3.0, $calc['breakdown'][0]['hours']);
        $this->assertEquals(45000, $calc['breakdown'][0]['subtotal']);
        $this->assertEquals(65000, $calc['total_pay']); // 45.000 + 20.000 meal
    }
}
