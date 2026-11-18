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
        // HR area redirects regular employee to home (AdminMiddleware)
        $this->actingAs($this->regularUser)
            ->get(route('hr.division-savings'))
            ->assertRedirect(route('home'));

        // Payroll area forbids regular employee with 403 (PayrollMiddleware)
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

    public function test_admin_can_deposit_credit_into_existing_wallet(): void
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

        $wallet->refresh();
        $this->assertEquals(250000, $wallet->current_balance);

        $this->assertDatabaseHas('division_saving_transactions', [
            'division_id' => $this->div1->id,
            'wallet_id' => $wallet->id,
            'transaction_type' => 'credit',
            'amount' => 150000,
            'balance_after' => 250000,
            'description' => 'Setoran iuran kas mingguan',
            'created_by' => $this->adminDiv1->id,
        ]);
    }

    public function test_admin_can_create_new_wallet_on_the_fly_and_record_credit(): void
    {
        Livewire::actingAs($this->adminDiv1)
            ->test(DivisionSavingComponent::class)
            ->call('openTransactionModal')
            ->set('form_wallet_mode', 'new')
            ->set('form_new_wallet_title', 'Dana Taktis Lembur')
            ->set('form_type', 'credit')
            ->set('form_amount', 300000)
            ->set('form_description', 'Modal awal kas taktis')
            ->call('saveTransaction')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('division_wallets', [
            'division_id' => $this->div1->id,
            'title' => 'Dana Taktis Lembur',
            'current_balance' => 300000,
        ]);

        $this->assertDatabaseHas('division_saving_transactions', [
            'division_id' => $this->div1->id,
            'amount' => 300000,
            'balance_after' => 300000,
            'transaction_type' => 'credit',
        ]);
    }

    public function test_debit_deducts_wallet_balance_and_records_correctly(): void
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

        $wallet->refresh();
        $this->assertEquals(125000, $wallet->current_balance);

        $this->assertDatabaseHas('division_saving_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => 'debit',
            'amount' => 75000,
            'balance_after' => 125000,
        ]);
    }

    public function test_overdraft_protection_prevents_debit_exceeding_balance(): void
    {
        $wallet = DivisionWallet::create([
            'division_id' => $this->div1->id,
            'title' => 'Kas Kecil',
            'current_balance' => 50000,
            'created_by' => $this->adminDiv1->id,
        ]);

        $component = Livewire::actingAs($this->adminDiv1)
            ->test(DivisionSavingComponent::class)
            ->call('openTransactionModal', $wallet->id, 'debit')
            ->set('form_amount', 100000)
            ->set('form_description', 'Pengeluaran melebihi saldo')
            ->call('saveTransaction');

        $this->assertStringContainsString('tidak mencukupi', $component->get('errorMessage'));

        $wallet->refresh();
        $this->assertEquals(50000, $wallet->current_balance);
    }

    public function test_admin_cannot_transact_or_tamper_for_another_division(): void
    {
        // Admin Div1 tries to force transaction for Div2 via service directly
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        DivisionSavingService::recordTransaction(
            divisionId: $this->div2->id,
            walletOrTitle: 'Hacking Wallet',
            type: 'credit',
            amount: 50000,
            description: 'Illegal transaction attempt',
            actor: $this->adminDiv1
        );
    }

    public function test_owner_has_global_access_and_can_transact_for_any_division(): void
    {
        Livewire::actingAs($this->ownerUser)
            ->test(DivisionSavingComponent::class)
            ->assertSee('Divisi Percetakan')
            ->assertSee('Divisi Desain')
            ->set('selectedDivisionId', $this->div2->id)
            ->call('openTransactionModal')
            ->set('form_division_id', $this->div2->id)
            ->set('form_wallet_mode', 'new')
            ->set('form_new_wallet_title', 'Suntikan Modal Owner')
            ->set('form_type', 'credit')
            ->set('form_amount', 2000000)
            ->set('form_description', 'Injeksi dana owner ke divisi desain')
            ->call('saveTransaction')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('division_wallets', [
            'division_id' => $this->div2->id,
            'title' => 'Suntikan Modal Owner',
            'current_balance' => 2000000,
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
            actor: $this->adminDiv1
        );

        DivisionSavingService::recordTransaction(
            divisionId: $this->div2->id,
            walletOrTitle: $walletDiv2,
            type: 'credit',
            amount: 500000,
            description: 'Setoran Divisi 2',
            actor: $this->adminDiv2
        );

        $this->assertEquals(100000, $walletDiv1->fresh()->current_balance);
        $this->assertEquals(500000, $walletDiv2->fresh()->current_balance);
    }
}
