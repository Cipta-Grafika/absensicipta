<?php

namespace Tests\Feature;

use App\Livewire\Admin\DivisionSavingComponent;
use App\Models\Division;
use App\Models\DivisionSavingTransaction;
use App\Models\DivisionWallet;
use App\Models\Education;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\DivisionSavingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DivisionSavingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Division $div1;
    private Division $div2;
    private User $adminDiv1;
    private User $adminDiv2;
    private User $ownerUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $edu = Education::firstOrCreate(['name' => 'S1']);
        $job = JobTitle::firstOrCreate(['name' => 'Staff']);

        $this->div1 = Division::create(['name' => 'Divisi Percetakan']);
        $this->div2 = Division::create(['name' => 'Divisi Desain']);

        $this->adminDiv1 = User::factory()->create([
            'group' => 'admin',
            'division_id' => $this->div1->id,
            'education_id' => $edu->id,
            'job_title_id' => $job->id,
        ]);

        $this->adminDiv2 = User::factory()->create([
            'group' => 'admin',
            'division_id' => $this->div2->id,
            'education_id' => $edu->id,
            'job_title_id' => $job->id,
        ]);

        $this->ownerUser = User::factory()->create([
            'group' => 'owner',
            'division_id' => $this->div1->id,
            'education_id' => $edu->id,
            'job_title_id' => $job->id,
        ]);

        $this->regularUser = User::factory()->create([
            'group' => 'user',
            'division_id' => $this->div1->id,
            'education_id' => $edu->id,
            'job_title_id' => $job->id,
        ]);
    }

    public function test_admin_can_access_division_savings_routes(): void
    {
        $this->actingAs($this->adminDiv1)
            ->get(route('hr.division-savings'))
            ->assertStatus(200)
            ->assertSee('Tabungan Divisi');

        $this->actingAs($this->adminDiv1)
            ->get(route('payroll.division-savings'))
            ->assertStatus(200)
            ->assertSee('Tabungan Divisi');
    }

    public function test_regular_user_is_strictly_forbidden_from_accessing_division_savings(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('hr.division-savings'))
            ->assertRedirect(route('home'));

        $this->actingAs($this->regularUser)
            ->get(route('payroll.division-savings'))
            ->assertStatus(403);
    }

    public function test_admin_is_strictly_scoped_to_own_division_wallets(): void
    {
        $wallet1 = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Produksi Percetakan',
            'current_balance' => 500000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $wallet2 = DivisionWallet::create([
            'division_id' => $this->div2->id,
            'title' => 'Kas Studio Desain Rahasia',
            'current_balance' => 750000,
            'created_by' => $this->adminDiv2->id,
        ]);

        Livewire::actingAs($this->adminDiv1)
            ->test(DivisionSavingComponent::class)
            ->assertSee('Kas Produksi Percetakan')
            ->assertDontSee('Kas Studio Desain Rahasia');
    }

    public function test_admin_submission_creates_pending_transaction_without_altering_wallet_balance(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Dana Sosial Divisi',
            'current_balance' => 100000,
            'created_by' => $this->adminDiv1->id,
        ]);

        Livewire::actingAs($this->adminDiv1)
            ->test(DivisionSavingComponent::class)
            ->call('openTransactionModal', $wallet->id, 'credit')
            ->set('form_amount', 150000)
            ->set('form_description', 'Setoran iuran kas mingguan')
            ->call('saveTransaction')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        // Saldo dompet TIDAK boleh bertambah sebelum disetujui Owner
        $wallet->refresh();
        $this->assertEquals(100000, $wallet->current_balance);

        $this->assertDatabaseHas('division_saving_transactions', [
            'division_id' => $this->div1->id,
            'wallet_id' => $wallet->id,
            'transaction_type' => 'credit',
            'status' => 'pending',
            'amount' => 150000,
            'balance_before' => null,
            'balance_after' => null,
            'created_by' => $this->adminDiv1->id,
            'approved_by' => null,
        ]);
    }

    public function test_owner_can_approve_pending_credit_transaction_and_balance_updates(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Dana Taktis Lembur',
            'current_balance' => 100000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $pendingTx = DivisionSavingService::recordTransaction(
            divisionId: $this->div1->id,
            walletOrTitle: $wallet,
            type: 'credit',
            amount: 200000,
            description: 'Penambahan kas taktis lembur',
            actor: $this->adminDiv1
        );

        $this->assertEquals('pending', $pendingTx->status);
        $this->assertEquals(100000, $wallet->fresh()->current_balance);

        // Owner melakukan approval
        Livewire::actingAs($this->ownerUser)
            ->test(DivisionSavingComponent::class)
            ->call('openApproveModal', $pendingTx->id)
            ->call('executeApprove')
            ->assertDispatched('notify');

        $pendingTx->refresh();
        $wallet->refresh();

        $this->assertEquals('approved', $pendingTx->status);
        $this->assertEquals($this->ownerUser->id, $pendingTx->approved_by);
        $this->assertNotNull($pendingTx->approved_at);
        $this->assertEquals(100000, (float) $pendingTx->balance_before);
        $this->assertEquals(300000, (float) $pendingTx->balance_after);
        $this->assertEquals(300000, (float) $wallet->current_balance);
    }

    public function test_admin_submission_debit_creates_pending_without_deducting_balance(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Konsumsi',
            'current_balance' => 200000,
            'created_by' => $this->adminDiv1->id,
        ]);

        Livewire::actingAs($this->adminDiv1)
            ->test(DivisionSavingComponent::class)
            ->call('openTransactionModal', $wallet->id, 'debit')
            ->set('form_amount', 75000)
            ->set('form_description', 'Beli snack lembur')
            ->call('saveTransaction')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        // Saldo dompet TIDAK boleh berkurang sebelum disetujui Owner
        $wallet->refresh();
        $this->assertEquals(200000, $wallet->current_balance);

        $this->assertDatabaseHas('division_saving_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => 'debit',
            'status' => 'pending',
            'amount' => 75000,
            'balance_after' => null,
        ]);
    }

    public function test_owner_can_approve_pending_debit_and_balance_deducts(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Operasional',
            'current_balance' => 500000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $pendingTx = DivisionSavingService::recordTransaction(
            divisionId: $this->div1->id,
            walletOrTitle: $wallet,
            type: 'debit',
            amount: 150000,
            description: 'Beli kertas & tinta tambahan',
            actor: $this->adminDiv1
        );

        $this->assertEquals(500000, $wallet->fresh()->current_balance);

        Livewire::actingAs($this->ownerUser)
            ->test(DivisionSavingComponent::class)
            ->call('openApproveModal', $pendingTx->id)
            ->call('executeApprove')
            ->assertDispatched('notify');

        $wallet->refresh();
        $this->assertEquals(350000, $wallet->current_balance);

        $pendingTx->refresh();
        $this->assertEquals('approved', $pendingTx->status);
        $this->assertEquals(500000, (float) $pendingTx->balance_before);
        $this->assertEquals(350000, (float) $pendingTx->balance_after);
    }

    public function test_overdraft_protection_on_approval_blocks_insufficient_balance(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Kecil',
            'current_balance' => 50000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $pendingTx = DivisionSavingService::recordTransaction(
            divisionId: $this->div1->id,
            walletOrTitle: $wallet,
            type: 'debit',
            amount: 100000,
            description: 'Pengeluaran melebihi saldo kas',
            actor: $this->adminDiv1
        );

        $component = Livewire::actingAs($this->ownerUser)
            ->test(DivisionSavingComponent::class)
            ->call('openApproveModal', $pendingTx->id)
            ->call('executeApprove');

        $this->assertStringContainsString('tidak mencukupi', $component->get('errorMessage'));

        // Saldo tetap 50.000 dan status transaksi tetap pending
        $wallet->refresh();
        $this->assertEquals(50000, $wallet->current_balance);
        $this->assertEquals('pending', $pendingTx->fresh()->status);
    }

    public function test_owner_can_reject_pending_transaction_with_reason(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Acara',
            'current_balance' => 100000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $pendingTx = DivisionSavingService::recordTransaction(
            divisionId: $this->div1->id,
            walletOrTitle: $wallet,
            type: 'credit',
            amount: 50000,
            description: 'Setoran tanpa bukti transfer yang jelas',
            actor: $this->adminDiv1
        );

        Livewire::actingAs($this->ownerUser)
            ->test(DivisionSavingComponent::class)
            ->call('openRejectModal', $pendingTx->id)
            ->set('rejectionReason', 'Bukti transfer tidak valid / tidak terbaca')
            ->call('executeReject')
            ->assertDispatched('notify');

        $pendingTx->refresh();
        $this->assertEquals('rejected', $pendingTx->status);
        $this->assertEquals($this->ownerUser->id, $pendingTx->rejected_by);
        $this->assertEquals('Bukti transfer tidak valid / tidak terbaca', $pendingTx->rejection_note);

        // Saldo tidak berubah
        $wallet->refresh();
        $this->assertEquals(100000, $wallet->current_balance);
    }

    public function test_non_owner_cannot_approve_or_reject_transaction(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Perlengkapan',
            'current_balance' => 100000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $pendingTx = DivisionSavingService::recordTransaction(
            divisionId: $this->div1->id,
            walletOrTitle: $wallet,
            type: 'credit',
            amount: 50000,
            description: 'Uji wewenang approval',
            actor: $this->adminDiv1
        );

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        DivisionSavingService::approveTransaction($pendingTx, $this->adminDiv1);
    }

    public function test_admin_can_cancel_their_own_pending_submission(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Khusus',
            'current_balance' => 100000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $pendingTx = DivisionSavingService::recordTransaction(
            divisionId: $this->div1->id,
            walletOrTitle: $wallet,
            type: 'credit',
            amount: 50000,
            description: 'Pengajuan salah ketik nominal',
            actor: $this->adminDiv1
        );

        Livewire::actingAs($this->adminDiv1)
            ->test(DivisionSavingComponent::class)
            ->call('cancelTransaction', $pendingTx->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('division_saving_transactions', [
            'id' => $pendingTx->id,
        ]);
    }

    public function test_owner_direct_transaction_is_auto_approved_by_default(): void
    {
        Livewire::actingAs($this->ownerUser)
            ->test(DivisionSavingComponent::class)
            ->set('selectedDivisionId', $this->div2->id)
            ->call('openTransactionModal')
            ->set('form_division_id', $this->div2->id)
            ->set('form_wallet_mode', 'new')
            ->set('form_new_wallet_title', 'Suntikan Modal Langsung Owner')
            ->set('form_type', 'credit')
            ->set('form_amount', 2000000)
            ->set('form_description', 'Injeksi modal langsung owner')
            ->set('form_auto_approve', true)
            ->call('saveTransaction')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('division_wallets', [
            'division_id' => $this->div2->id,
            'title' => 'Suntikan Modal Langsung Owner',
            'current_balance' => 2000000,
        ]);

        $this->assertDatabaseHas('division_saving_transactions', [
            'division_id' => $this->div2->id,
            'status' => 'approved',
            'amount' => 2000000,
            'balance_after' => 2000000,
        ]);
    }

    public function test_identical_wallet_titles_can_coexist_independently_across_divisions(): void
    {
        $walletDiv1 = DivisionSavingService::findOrCreateWallet($this->div1->id, 'Kas Operasional', $this->adminDiv1);
        $walletDiv2 = DivisionSavingService::findOrCreateWallet($this->div2->id, 'Kas Operasional', $this->adminDiv2);

        $this->assertNotEquals($walletDiv1->id, $walletDiv2->id);
        $this->assertEquals($this->div1->id, $walletDiv1->division_id);
        $this->assertEquals($this->div2->id, $walletDiv2->division_id);

        DivisionSavingService::recordTransaction(
            divisionId: $this->div1->id,
            walletOrTitle: $walletDiv1,
            type: 'credit',
            amount: 100000,
            description: 'Setoran Divisi 1',
            actor: $this->ownerUser
        );

        DivisionSavingService::recordTransaction(
            divisionId: $this->div2->id,
            walletOrTitle: $walletDiv2,
            type: 'credit',
            amount: 500000,
            description: 'Setoran Divisi 2',
            actor: $this->ownerUser
        );

        $this->assertEquals(100000, $walletDiv1->fresh()->current_balance);
        $this->assertEquals(500000, $walletDiv2->fresh()->current_balance);
    }
}
