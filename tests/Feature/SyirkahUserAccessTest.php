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

test('syirkah user can edit transaction nominal amounts and recalculates running balances', function () {
    $syirkahUser = User::factory()->create(['group' => 'syirkah']);
    $employee = User::factory()->create(['group' => 'user']);
    $saving = \App\Models\Saving::create([
        'savings_name' => 'Syirkah Test',
        'mandatory_amount' => 100000,
        'secondary_amount' => 50000,
    ]);

    $transaction = \App\Models\SavingTransaction::create([
        'user_id' => $employee->id,
        'savings_id' => $saving->id,
        'transaction_type' => 'deposit',
        'mandatory_amount' => 100000,
        'secondary_amount' => 50000,
        'balance_mandatory' => 100000,
        'balance_secondary' => 50000,
        'description' => 'Initial deposit',
    ]);

    \Illuminate\Support\Facades\DB::table('saving_transactions')
        ->where('id', $transaction->id)
        ->update([
            'created_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'updated_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
        ]);

    $transaction->refresh();

    $this->actingAs($syirkahUser);

    \Livewire\Livewire::test(\App\Livewire\Payroll\SavingTransactionComponent::class)
        ->call('openEditNominalModal', $transaction->id)
        ->set('edit_mandatory_amount', 200000)
        ->set('edit_secondary_amount', 150000)
        ->call('updateNominal')
        ->assertHasNoErrors();

    $transaction->refresh();
    expect((float)$transaction->mandatory_amount)->toBe(200000.0);
    expect((float)$transaction->secondary_amount)->toBe(150000.0);
    expect($transaction->updated_at->timestamp)->toBeGreaterThan($transaction->created_at->timestamp);

    // Check summary update
    $summary = \App\Models\SavingSummary::where('user_id', $employee->id)->where('savings_id', $saving->id)->first();
    expect((float)$summary->total_mandatory)->toBe(200000.0);
    expect((float)$summary->total_secondary)->toBe(150000.0);
});

test('non-syirkah user cannot edit transaction nominals', function () {
    $payrollUser = User::factory()->create(['group' => 'payroll']);
    $employee = User::factory()->create(['group' => 'user']);
    $saving = \App\Models\Saving::create([
        'savings_name' => 'Syirkah Test 2',
        'mandatory_amount' => 100000,
        'secondary_amount' => 50000,
    ]);

    $transaction = \App\Models\SavingTransaction::create([
        'user_id' => $employee->id,
        'savings_id' => $saving->id,
        'transaction_type' => 'deposit',
        'mandatory_amount' => 100000,
        'secondary_amount' => 50000,
        'balance_mandatory' => 100000,
        'balance_secondary' => 50000,
        'description' => 'Initial deposit',
    ]);

    $this->actingAs($payrollUser);

    \Livewire\Livewire::test(\App\Livewire\Payroll\SavingTransactionComponent::class)
        ->call('openEditNominalModal', $transaction->id)
        ->assertStatus(403);
});

test('syirkah user can access and manipulate employee syirkah transactions across multiple divisions', function () {
    $syirkahUser = User::factory()->create(['group' => 'syirkah']);

    $divA = \App\Models\Division::create(['name' => 'Divisi Marketing']);
    $divB = \App\Models\Division::create(['name' => 'Divisi IT']);

    $empA = User::factory()->create(['group' => 'user', 'division_id' => $divA->id]);
    $empB = User::factory()->create(['group' => 'user', 'division_id' => $divB->id]);

    $saving = \App\Models\Saving::create([
        'savings_name' => 'Syirkah Multi Divisi',
        'mandatory_amount' => 100000,
        'secondary_amount' => 50000,
    ]);

    $txA = \App\Models\SavingTransaction::create([
        'user_id' => $empA->id,
        'savings_id' => $saving->id,
        'transaction_type' => 'deposit',
        'mandatory_amount' => 100000,
        'secondary_amount' => 50000,
        'balance_mandatory' => 100000,
        'balance_secondary' => 50000,
        'description' => 'Deposit Div A',
    ]);

    $txB = \App\Models\SavingTransaction::create([
        'user_id' => $empB->id,
        'savings_id' => $saving->id,
        'transaction_type' => 'deposit',
        'mandatory_amount' => 150000,
        'secondary_amount' => 75000,
        'balance_mandatory' => 150000,
        'balance_secondary' => 75000,
        'description' => 'Deposit Div B',
    ]);

    $this->actingAs($syirkahUser);

    \Livewire\Livewire::test(\App\Livewire\Payroll\SavingTransactionComponent::class)
        ->assertSee($empA->name)
        ->assertSee($empB->name)
        ->call('openEditNominalModal', $txA->id)
        ->set('edit_mandatory_amount', 300000)
        ->call('updateNominal')
        ->assertHasNoErrors();

    expect((float)$txA->fresh()->mandatory_amount)->toBe(300000.0);
});
