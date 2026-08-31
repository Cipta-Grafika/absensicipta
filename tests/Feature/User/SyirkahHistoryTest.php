<?php

namespace Tests\Feature\User;

use App\Livewire\User\SyirkahHistoryComponent;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SyirkahHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_syirkah(): void
    {
        $response = $this->get('/syirkah');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_syirkah_page(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/syirkah');
        $response->assertStatus(200);
        $response->assertSee('Riwayat Syirkah');
    }

    public function test_syirkah_page_only_displays_approved_transactions(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Mudharabah',
            'mandatory_savings' => 50000,
            'secondary_savings' => 25000,
        ]);

        // 1. Approved deposit
        $approvedTx = SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 50000,
            'secondary_amount' => 25000,
            'balance_mandatory' => 50000,
            'balance_secondary' => 25000,
            'description' => 'Setoran Payroll Periode Agustus',
            'status' => 'approved',
        ]);

        // 2. Pending deposit (should NOT be shown in ledger)
        $pendingTx = SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 100000,
            'secondary_amount' => 50000,
            'balance_mandatory' => 150000,
            'balance_secondary' => 75000,
            'description' => 'Setoran Tertunda September',
            'status' => 'pending',
        ]);

        // 3. Rejected deposit (should NOT be shown in ledger)
        $rejectedTx = SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 200000,
            'secondary_amount' => 100000,
            'balance_mandatory' => 0,
            'balance_secondary' => 0,
            'description' => 'Setoran Ditolak',
            'status' => 'rejected',
        ]);

        $this->actingAs($user);

        Livewire::test(SyirkahHistoryComponent::class)
            ->assertSee('Setoran Payroll Periode Agustus')
            ->assertDontSee('Setoran Tertunda September')
            ->assertDontSee('Setoran Ditolak')
            ->assertSee('Rp 75.000'); // Total Saldo: 50.000 + 25.000 = 75.000
    }

    public function test_debit_and_credit_calculations_and_filters(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_savings' => 100000,
            'secondary_savings' => 50000,
        ]);

        // Deposit 1 (Credit): 100.000 Wajib, 50.000 SSR => Total +150.000
        SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 100000,
            'secondary_amount' => 50000,
            'balance_mandatory' => 100000,
            'balance_secondary' => 50000,
            'description' => 'Setoran Awal Bulan',
            'status' => 'approved',
            'created_at' => '2026-08-01 10:00:00',
        ]);

        // Withdrawal 1 (Debit): 30.000 SSR => Total -30.000
        SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'withdrawal',
            'mandatory_amount' => 0,
            'secondary_amount' => 30000,
            'balance_mandatory' => 100000,
            'balance_secondary' => 20000,
            'description' => 'Penarikan SSR Parsial',
            'status' => 'approved',
            'created_at' => '2026-08-15 14:00:00',
        ]);

        $this->actingAs($user);

        // Test render with totals:
        // Wajib = 100.000, SSR = 20.000, Total Saldo = 120.000
        // Total Credit = 150.000, Total Debit = 30.000
        Livewire::test(SyirkahHistoryComponent::class)
            ->assertViewHas('saldoWajib', 100000.0)
            ->assertViewHas('saldoSukarela', 20000.0)
            ->assertViewHas('totalSaldo', 120000.0)
            ->assertViewHas('totalCreditAll', 150000.0)
            ->assertViewHas('totalDebitAll', 30000.0)
            ->assertSee('+ Rp 150.000')
            ->assertSee('- Rp 30.000')
            ->assertSee('Setoran Awal Bulan')
            ->assertSee('Penarikan SSR Parsial')
            // Test Type filter = deposit
            ->set('type', 'deposit')
            ->assertSee('Setoran Awal Bulan')
            ->assertDontSee('Penarikan SSR Parsial')
            // Test Type filter = withdrawal
            ->set('type', 'withdrawal')
            ->assertSee('Penarikan SSR Parsial')
            ->assertDontSee('Setoran Awal Bulan')
            // Test Search filter
            ->set('type', '')
            ->set('search', 'Awal')
            ->assertSee('Setoran Awal Bulan')
            ->assertDontSee('Penarikan SSR Parsial');
    }

    public function test_user_can_open_and_close_transaction_detail_modal(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'name' => 'Bendahara Syirkah',
            'group' => 'payroll',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Qardh',
            'mandatory_savings' => 50000,
            'secondary_savings' => 20000,
        ]);

        $tx = SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 50000,
            'secondary_amount' => 20000,
            'balance_mandatory' => 50000,
            'balance_secondary' => 20000,
            'reference_type' => 'payroll',
            'description' => 'Pemotongan Otomatis Gaji Bulan Agustus',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approval_date' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(SyirkahHistoryComponent::class)
            ->assertSet('isDetailModalOpen', false)
            ->call('openDetailModal', $tx->id)
            ->assertSet('isDetailModalOpen', true)
            ->assertSee('Pemotongan Otomatis Gaji Bulan Agustus')
            ->assertSee('Syirkah Qardh')
            ->assertSee('Bendahara Syirkah')
            ->call('closeDetailModal')
            ->assertSet('isDetailModalOpen', false);
    }
}
