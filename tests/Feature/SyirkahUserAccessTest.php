<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('syirkah user group can access allowed syirkah pages with 200 OK', function () {
    $user = User::factory()->create([
        'group' => 'syirkah',
    ]);

    $this->actingAs($user);

    $allowedRoutes = [
        'payroll.saving-transactions',
        'payroll.loans',
        'payroll.savings',
        'payroll.import-export.savings',
        'payroll.import-export.saving-transactions',
    ];

    foreach ($allowedRoutes as $routeName) {
        $this->get(route($routeName))->assertStatus(200);
    }
});

test('syirkah user group is forbidden 403 when accessing unauthorized payroll and admin routes', function () {
    $user = User::factory()->create([
        'group' => 'syirkah',
    ]);

    $this->actingAs($user);

    $forbiddenRoutes = [
        'payroll.dashboard',
        'payroll.employee-salaries',
        'payroll.payment-methods',
        'payroll.history',
        'payroll.import-export.employee-salaries',
        'payroll.import-export.payment-methods',
        'hr.dashboard',
        'home',
    ];

    foreach ($forbiddenRoutes as $routeName) {
        $this->get(route($routeName))->assertStatus(403);
    }
});

test('syirkah user is redirected to saving-transactions upon visiting root url', function () {
    $user = User::factory()->create([
        'group' => 'syirkah',
    ]);

    $this->actingAs($user);

    $this->get('/')->assertRedirect(route('payroll.saving-transactions'));
});

test('payroll user group retains full access to all payroll routes', function () {
    $user = User::factory()->create([
        'group' => 'payroll',
    ]);

    $this->actingAs($user);

    $allPayrollRoutes = [
        'payroll.dashboard',
        'payroll.employee-salaries',
        'payroll.payment-methods',
        'payroll.history',
        'payroll.savings',
        'payroll.saving-transactions',
        'payroll.loans',
        'payroll.import-export.employee-salaries',
        'payroll.import-export.payment-methods',
        'payroll.import-export.savings',
        'payroll.import-export.saving-transactions',
    ];

    foreach ($allPayrollRoutes as $routeName) {
        $this->get(route($routeName))->assertStatus(200);
    }
});
