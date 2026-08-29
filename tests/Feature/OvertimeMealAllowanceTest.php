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

        // Case 5: Start at 12:00 Daytime (NOT Eligible) -> 4h: 65.000 (meal = 0)
        $calc5 = OvertimeRate::calculatePayForDuration(4.0, $user, '12:00:00', '16:00:00', '2026-08-11');
        $this->assertEquals(0, $calc5['meal_allowance']);
        $this->assertEquals(65000, $calc5['total_pay']);
    }
}
