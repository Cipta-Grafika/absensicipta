<?php

namespace Tests\Feature\User;

use App\Livewire\User\SyirkahHistoryComponent;
use App\Models\EmployeeSalary;
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

    public function test_user_withdrawal_modal_starts_from_zero_and_submits_cleanly(): void
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

        // Create initial deposit so balance is available
        SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 2000000,
            'secondary_amount' => 900000,
            'balance_mandatory' => 2000000,
            'balance_secondary' => 900000,
            'description' => 'Saldo Awal',
            'status' => 'approved',
        ]);

        $this->actingAs($user);

        Livewire::test(SyirkahHistoryComponent::class)
            ->call('openWithdrawalModal')
            ->assertSet('isWithdrawalModalOpen', true)
            ->assertSet('mandatoryAmount', 0)
            ->assertSet('secondaryAmount', 0)
            ->set('mandatoryAmount', 200000)
            ->set('secondaryAmount', 100000)
            ->call('submitWithdrawal')
            ->assertSet('isWithdrawalModalOpen', false)
            ->assertDispatched('notify', 'Pengajuan penarikan syirkah berhasil dikirim. Menunggu verifikasi Manajer Divisi.');

        $this->assertDatabaseHas('saving_withdrawals', [
            'user_id' => $user->id,
            'mandatory_amount' => 200000,
            'secondary_amount' => 100000,
            'total_amount' => 300000,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_open_override_modal_and_see_master_program_info(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Reguler 2026',
            'mandatory_savings' => 50000,
            'secondary_savings' => 50000,
        ]);

        $salary = EmployeeSalary::create([
            'employee_id' => $user->id,
            'salary_type' => 'monthly',
            'basic_salary' => 4000000,
            'savings_id' => $saving->id,
            'custom_secondary_savings' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(SyirkahHistoryComponent::class)
            ->call('openOverrideModal')
            ->assertSet('isOverrideModalOpen', true)
            ->assertSet('masterSavingsName', 'Syirkah Reguler 2026')
            ->assertSet('masterMandatorySavings', 50000.0)
            ->assertSet('masterSecondarySavings', 50000.0)
            ->assertSet('overrideMode', 'default')
            ->assertSet('overrideNominal', 50000.0)
            ->assertSet('hasCustomOverride', false)
            ->assertSee('Syirkah Reguler 2026')
            ->assertSee('Default Master');
    }

    public function test_user_can_set_custom_secondary_savings_override_and_it_persists(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Reguler 2026',
            'mandatory_savings' => 50000,
            'secondary_savings' => 50000,
        ]);

        $salary = EmployeeSalary::create([
            'employee_id' => $user->id,
            'salary_type' => 'monthly',
            'basic_salary' => 4000000,
            'savings_id' => $saving->id,
            'custom_secondary_savings' => null,
        ]);

        $this->actingAs($user);

        // User chooses Custom mode and sets nominal to 150000
        Livewire::test(SyirkahHistoryComponent::class)
            ->call('openOverrideModal')
            ->assertSet('isOverrideModalOpen', true)
            ->set('overrideMode', 'custom')
            ->set('overrideNominal', 150000)
            ->call('saveOverride')
            ->assertSet('isOverrideModalOpen', false)
            ->assertDispatched('notify', 'Pengaturan nominal Syirkah Sukarela berhasil diubah.');

        $salary->refresh();
        expect($salary->custom_secondary_savings)->toEqual(150000.0)
            ->and($salary->effective_secondary_savings)->toEqual(150000.0);

        // Verify page reflects the custom setting
        Livewire::test(SyirkahHistoryComponent::class)
            ->assertViewHas('userHasCustomOverride', true)
            ->assertViewHas('userEffectiveSecondary', 150000.0)
            ->assertSee('Custom')
            ->assertSee('Rp 150.000/bln');
    }

    public function test_user_can_use_preset_buttons_and_reset_override_to_default(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Reguler 2026',
            'mandatory_savings' => 50000,
            'secondary_savings' => 50000,
        ]);

        $salary = EmployeeSalary::create([
            'employee_id' => $user->id,
            'salary_type' => 'monthly',
            'basic_salary' => 4000000,
            'savings_id' => $saving->id,
            'custom_secondary_savings' => 200000,
        ]);

        $this->actingAs($user);

        // 1. Test preset helper
        Livewire::test(SyirkahHistoryComponent::class)
            ->call('openOverrideModal')
            ->assertSet('overrideMode', 'custom')
            ->assertSet('overrideNominal', 200000.0)
            ->call('setOverridePreset', 300000)
            ->assertSet('overrideNominal', 300000.0)
            ->call('saveOverride')
            ->assertSet('isOverrideModalOpen', false);

        $salary->refresh();
        expect($salary->custom_secondary_savings)->toEqual(300000.0);

        // 2. Test reset back to default master
        Livewire::test(SyirkahHistoryComponent::class)
            ->call('openOverrideModal')
            ->set('overrideMode', 'default')
            ->call('saveOverride')
            ->assertSet('isOverrideModalOpen', false)
            ->assertDispatched('notify', 'Nominal Syirkah Sukarela dikembalikan ke default master.');

        $salary->refresh();
        expect($salary->custom_secondary_savings)->toBeNull()
            ->and($salary->effective_secondary_savings)->toEqual(50000.0);
    }

    public function test_pool_kas_talangan_transactions_are_excluded_and_cleaned_from_user_syirkah_history(): void
    {
        $user = User::factory()->create([
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Mudharabah',
            'mandatory_savings' => 100000,
            'secondary_savings' => 50000,
        ]);

        // Regular deposit
        SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 1900000,
            'secondary_amount' => 850000,
            'description' => 'Saldo Awal Syirkah',
            'status' => 'approved',
        ]);

        // Create a pool loan
        $poolLoan = \App\Models\Loan::create([
            'user_id' => $user->id,
            'loan_amount' => 3120000,
            'tenor_months' => 6,
            'installment_amount' => 520000,
            'remaining_balance' => 3120000,
            'payment_source' => 'payroll',
            'disbursement_source' => 'syirkah_pool_secondary',
            'syirkah_destination' => 'syirkah_pool_secondary',
            'status' => 'active',
            'description' => 'Trip Singapore 2026',
        ]);

        // Simulate an invalid legacy transaction mentioning Kas Talangan
        $legacyTx = SavingTransaction::create([
            'user_id' => $user->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'withdrawal',
            'mandatory_amount' => 0,
            'secondary_amount' => 3120000,
            'reference_type' => 'loan_disbursement',
            'reference_id' => $poolLoan->id,
            'description' => 'Pencairan Pinjaman (Kas Talangan Syirkah Sukarela): Trip Singapore 2026',
            'status' => 'approved',
        ]);

        $this->actingAs($user);

        // Accessing component should trigger cleanup and exclude pool transaction
        Livewire::test(SyirkahHistoryComponent::class)
            ->assertSee('Saldo Awal Syirkah')
            ->assertDontSee('Pencairan Pinjaman (Kas Talangan Syirkah Sukarela)')
            ->assertDontSee('LOAN_DISBURSEMENT')
            ->assertViewHas('saldoSukarela', 850000.0)
            ->assertViewHas('saldoWajib', 1900000.0);

        // Verify that the legacy transaction is cleaned up in DB
        $this->assertDatabaseMissing('saving_transactions', [
            'id' => $legacyTx->id,
        ]);
    }
}

