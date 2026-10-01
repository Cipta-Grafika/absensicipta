<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Saving;
use App\Models\Division;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use Livewire\Livewire;
use App\Livewire\Payroll\SavingTransactionComponent;

class SyirkahSummaryFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_summary_adapts_to_filtering_on_transactions_tab(): void
    {
        $syirkahUser = User::factory()->create(['group' => 'syirkah']);
        $div1 = Division::create(['name' => 'Divisi Percetakan']);
        $div2 = Division::create(['name' => 'Divisi Kantor']);

        $emp1 = User::factory()->create(['group' => 'user', 'division_id' => $div1->id]);
        $emp2 = User::factory()->create(['group' => 'user', 'division_id' => $div2->id]);

        $saving = Saving::create([
            'savings_name' => 'Program Syirkah Pokok',
            'mandatory_savings' => 50000,
            'secondary_savings' => 20000,
        ]);

        // Transactions in August 2026 (Deposit) for emp1 (Div 1)
        SavingTransaction::create([
            'user_id' => $emp1->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
            'status' => 'approved',
            'created_at' => '2026-08-15 10:00:00',
        ]);

        // Transactions in August 2026 (Deposit) for emp2 (Div 2)
        SavingTransaction::create([
            'user_id' => $emp2->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 100000,
            'secondary_amount' => 50000,
            'status' => 'approved',
            'created_at' => '2026-08-20 10:00:00',
        ]);

        // Transactions in July 2026 (Deposit) for emp1
        SavingTransaction::create([
            'user_id' => $emp1->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 500000,
            'secondary_amount' => 250000,
            'status' => 'approved',
            'created_at' => '2026-07-10 10:00:00',
        ]);

        // Transactions in August 2026 (Withdrawal) for emp1
        SavingTransaction::create([
            'user_id' => $emp1->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'withdrawal',
            'mandatory_amount' => 0,
            'secondary_amount' => 10000,
            'status' => 'approved',
            'created_at' => '2026-08-25 10:00:00',
        ]);

        $this->actingAs($syirkahUser);

        // 1. Filter: activeTab=transactions, month=2026-08, type=deposit (All divisions)
        Livewire::test(SavingTransactionComponent::class)
            ->set('activeTab', 'transactions')
            ->set('month', '2026-08')
            ->set('type', 'deposit')
            ->assertViewHas('totalWajib', 150000)
            ->assertViewHas('totalSukarela', 75000)
            ->assertViewHas('totalMutasiAmount', 225000)
            ->assertViewHas('filteredTransactionsCount', 2)
            ->assertSee('Rp 150.000')
            ->assertSee('Rp 75.000')
            ->assertSee('Rp 225.000');

        // 2. Filter: activeTab=transactions, month=2026-08, type=deposit, division=div1
        Livewire::test(SavingTransactionComponent::class)
            ->set('activeTab', 'transactions')
            ->set('month', '2026-08')
            ->set('type', 'deposit')
            ->set('division', $div1->id)
            ->assertViewHas('totalWajib', 50000)
            ->assertViewHas('totalSukarela', 25000)
            ->assertViewHas('totalMutasiAmount', 75000)
            ->assertViewHas('filteredTransactionsCount', 1)
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 25.000')
            ->assertSee('Rp 75.000');

        // 3. Test resetFilters clears all filters
        Livewire::test(SavingTransactionComponent::class)
            ->set('activeTab', 'transactions')
            ->set('month', '2026-08')
            ->set('type', 'deposit')
            ->set('division', $div1->id)
            ->set('statusFilter', 'approved')
            ->set('search', 'Test')
            ->call('resetFilters')
            ->assertSet('month', '')
            ->assertSet('type', '')
            ->assertSet('division', '')
            ->assertSet('statusFilter', '')
            ->assertSet('search', '')
            ->assertSet('withdrawalMonth', '')
            ->assertSet('withdrawalStatusFilter', '')
            ->assertSet('withdrawalDivision', '')
            ->assertSet('withdrawalSearch', '');

        // 4. Test exportExcel triggers download
        $component = Livewire::test(SavingTransactionComponent::class)
            ->set('month', '2026-08')
            ->set('division', $div1->id)
            ->call('exportExcel');

        $component->assertFileDownloaded();
    }
}

