<?php

namespace Tests\Feature;

use App\Livewire\Payroll\SavingTransactionComponent;
use App\Livewire\User\SyirkahHistoryComponent;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SyirkahTransferProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_upload_transfer_proof_when_marking_withdrawal_paid()
    {
        $admin = User::factory()->create([
            'name' => 'Syirkah Admin',
            'group' => 'syirkah',
            'status' => 'active',
        ]);

        $employee = User::factory()->create([
            'name' => 'Employee John',
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Test',
            'mandatory_amount' => 50000,
        ]);

        // Pre-fill employee balance with approved deposit
        SavingTransaction::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 500000,
            'secondary_amount' => 500000,
            'balance_mandatory' => 500000,
            'balance_secondary' => 500000,
            'status' => 'approved',
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 200000,
            'total_amount' => 200000,
            'approved_mandatory_amount' => 0,
            'approved_secondary_amount' => 200000,
            'approved_total_amount' => 200000,
            'status' => 'approved',
            'reason' => 'Keperluan mendesak',
        ]);

        $this->actingAs($admin);

        $fakeProof = UploadedFile::fake()->image('bukti_transfer_sample.jpg', 600, 400);

        Livewire::test(SavingTransactionComponent::class)
            ->call('openPayWithdrawalModal', $withdrawal->id)
            ->set('paymentProof', $fakeProof)
            ->call('submitPayWithdrawal')
            ->assertHasNoErrors()
            ->assertSet('payWithdrawalModalOpen', false);

        $withdrawal->refresh();
        $this->assertEquals('paid', $withdrawal->status);
        $this->assertNotNull($withdrawal->transfer_proof);
        Storage::disk('public')->assertExists($withdrawal->transfer_proof);

        // Transaction is created and has the transfer proof
        $tx = SavingTransaction::where('reference_type', 'saving_withdrawal')
            ->where('reference_id', $withdrawal->id)
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals($withdrawal->transfer_proof, $tx->transfer_proof);
        $this->assertEquals($withdrawal->transfer_proof, $tx->effective_transfer_proof);
    }

    public function test_user_can_see_transfer_proof_in_syirkah_history()
    {
        $employee = User::factory()->create([
            'name' => 'Employee Jane',
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_amount' => 50000,
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 100000,
            'total_amount' => 100000,
            'status' => 'paid',
            'transfer_proof' => 'syirkah/proofs/test_receipt.jpg',
            'paid_at' => now(),
        ]);

        $tx = SavingTransaction::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'withdrawal',
            'mandatory_amount' => 0,
            'secondary_amount' => 100000,
            'balance_mandatory' => 0,
            'balance_secondary' => 0,
            'reference_type' => 'saving_withdrawal',
            'reference_id' => $withdrawal->id,
            'transfer_proof' => 'syirkah/proofs/test_receipt.jpg',
            'status' => 'approved',
        ]);

        $this->actingAs($employee);

        Livewire::test(SyirkahHistoryComponent::class)
            ->assertSee('test_receipt.jpg')
            ->call('openDetailModal', $tx->id)
            ->assertSet('isDetailModalOpen', true)
            ->assertSee('Bukti Transfer / Pembayaran')
            ->call('viewProof', asset('storage/syirkah/proofs/test_receipt.jpg'))
            ->assertSet('isProofModalOpen', true)
            ->call('closeProofModal')
            ->assertSet('isProofModalOpen', false);
    }

    public function test_direct_withdrawal_persists_transfer_proof()
    {
        $admin = User::factory()->create([
            'name' => 'Syirkah Direct Admin',
            'group' => 'syirkah',
            'status' => 'active',
        ]);

        $employee = User::factory()->create([
            'name' => 'Employee Bob',
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Program',
            'mandatory_amount' => 50000,
        ]);

        SavingTransaction::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 300000,
            'secondary_amount' => 300000,
            'balance_mandatory' => 300000,
            'balance_secondary' => 300000,
            'status' => 'approved',
        ]);

        $this->actingAs($admin);

        $fakeProof = UploadedFile::fake()->image('direct_payout.png', 400, 300);

        Livewire::test(SavingTransactionComponent::class)
            ->set('withdrawal_user_id', $employee->id)
            ->set('withdrawal_savings_id', $saving->id)
            ->set('withdrawal_amount', 150000)
            ->set('withdrawal_type', 'secondary')
            ->set('withdrawal_description', 'Pencairan manual kantor')
            ->set('withdrawal_transfer_proof', $fakeProof)
            ->call('processWithdrawal')
            ->assertHasNoErrors()
            ->assertSet('withdrawalModalOpen', false);

        $tx = SavingTransaction::where('user_id', $employee->id)
            ->where('transaction_type', 'withdrawal')
            ->first();

        $this->assertNotNull($tx);
        $this->assertNotNull($tx->transfer_proof);
        Storage::disk('public')->assertExists($tx->transfer_proof);
    }

    public function test_admin_or_owner_can_reupload_and_replace_proof_and_old_file_is_removed()
    {
        Storage::fake('public');

        $owner = User::factory()->create([
            'name' => 'Owner Boss',
            'group' => 'owner',
            'status' => 'active',
        ]);

        $employee = User::factory()->create([
            'name' => 'Employee Alice',
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_amount' => 50000,
        ]);

        // Upload old proof first
        $oldFile = UploadedFile::fake()->image('old_proof.png', 300, 200);
        $oldPath = $oldFile->store('syirkah/proofs', 'public');
        Storage::disk('public')->assertExists($oldPath);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 200000,
            'total_amount' => 200000,
            'status' => 'paid',
            'transfer_proof' => $oldPath,
            'paid_at' => now(),
        ]);

        $tx = SavingTransaction::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'withdrawal',
            'mandatory_amount' => 0,
            'secondary_amount' => 200000,
            'balance_mandatory' => 0,
            'balance_secondary' => 0,
            'reference_type' => 'saving_withdrawal',
            'reference_id' => $withdrawal->id,
            'transfer_proof' => $oldPath,
            'status' => 'approved',
        ]);

        $this->actingAs($owner);

        $newFile = UploadedFile::fake()->image('new_proof.png', 400, 300);

        Livewire::test(SavingTransactionComponent::class)
            ->call('openUploadProofModal', $withdrawal->id, 'withdrawal')
            ->assertSet('isUploadProofModalOpen', true)
            ->set('newTransferProof', $newFile)
            ->call('saveTransferProof')
            ->assertHasNoErrors()
            ->assertSet('isUploadProofModalOpen', false);

        $freshWd = $withdrawal->fresh();
        $freshTx = $tx->fresh();

        $this->assertNotEquals($oldPath, $freshWd->transfer_proof);
        $this->assertEquals($freshWd->transfer_proof, $freshTx->transfer_proof);

        // Old file MUST be deleted from storage
        Storage::disk('public')->assertMissing($oldPath);
        // New file MUST exist in storage
        Storage::disk('public')->assertExists($freshWd->transfer_proof);
    }

    public function test_admin_or_owner_can_delete_transfer_proof()
    {
        Storage::fake('public');

        $owner = User::factory()->create([
            'name' => 'Owner Boss',
            'group' => 'owner',
            'status' => 'active',
        ]);

        $employee = User::factory()->create([
            'name' => 'Employee Charlie',
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_amount' => 50000,
        ]);

        $file = UploadedFile::fake()->image('to_delete.png', 300, 200);
        $path = $file->store('syirkah/proofs', 'public');
        Storage::disk('public')->assertExists($path);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 100000,
            'total_amount' => 100000,
            'status' => 'paid',
            'transfer_proof' => $path,
            'paid_at' => now(),
        ]);

        $this->actingAs($owner);

        Livewire::test(SavingTransactionComponent::class)
            ->call('openUploadProofModal', $withdrawal->id, 'withdrawal')
            ->call('deleteTransferProof')
            ->assertSet('isUploadProofModalOpen', false);

        // File is purged from storage
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($withdrawal->fresh()->transfer_proof);
    }

    public function test_deleting_withdrawal_removes_proof_file_from_storage()
    {
        Storage::fake('public');

        $employee = User::factory()->create([
            'name' => 'Employee David',
            'group' => 'user',
            'status' => 'active',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_amount' => 50000,
        ]);

        $file = UploadedFile::fake()->image('withdraw_proof.png', 300, 200);
        $path = $file->store('syirkah/proofs', 'public');
        Storage::disk('public')->assertExists($path);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 100000,
            'total_amount' => 100000,
            'status' => 'paid',
            'transfer_proof' => $path,
            'paid_at' => now(),
        ]);

        $withdrawal->delete();

        // Old file must be removed from storage upon deletion
        Storage::disk('public')->assertMissing($path);
    }
}
