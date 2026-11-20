<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\PaymentMethod;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\User;
use App\Services\TelegramBotHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramBotSyirkahProofUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected User $employee;
    protected Division $division;
    protected Saving $saving;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Cache::flush();

        config(['services.telegram.bot_token' => 'test_token_12345']);

        Http::fake([
            'https://api.telegram.org/bottest_token_12345/getFile*' => Http::response([
                'ok' => true,
                'result' => [
                    'file_id' => 'photo_file_123',
                    'file_path' => 'photos/file_test_proof.jpg',
                ],
            ], 200),
            'https://api.telegram.org/file/bottest_token_12345/photos/file_test_proof.jpg' => Http::response(
                'FAKE_IMAGE_BINARY_DATA',
                200
            ),
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 999],
            ], 200),
        ]);

        $this->division = Division::create(['name' => 'Divisi Digital Printing']);

        $this->owner = User::factory()->create([
            'name' => 'Pak Owner',
            'group' => 'owner',
            'chat_code' => '999001',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Divisi',
            'group' => 'admin',
            'division_id' => $this->division->id,
            'chat_code' => '888001',
        ]);

        $this->employee = User::factory()->create([
            'name' => 'Zaenal Alfian',
            'group' => 'user',
            'division_id' => $this->division->id,
            'chat_code' => '777001',
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'user_id' => $this->employee->id,
            'payment_type' => 'bank_transfer',
            'payment_name' => 'BCA',
            'bank_account' => '1093223202',
            'account_name' => 'ZAENAL ALFIAN',
        ]);

        $this->saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_amount' => 50000,
        ]);

        // Seed initial balance for employee
        SavingTransaction::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 200000,
            'secondary_amount' => 300000,
            'balance_mandatory' => 200000,
            'balance_secondary' => 300000,
            'status' => 'approved',
        ]);
    }

    public function test_owner_clicking_paid_wd_prompts_to_upload_photo_or_pay_direct(): void
    {
        $wd = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 50000,
            'total_amount' => 50000,
            'approved_total_amount' => 50000,
            'status' => 'approved',
            'transfer_proof' => null,
        ]);

        TelegramBotHandler::handleUpdate([
            'update_id' => 1,
            'callback_query' => [
                'id' => 'cb_paid_1',
                'from' => ['id' => 999001, 'username' => 'owner_user'],
                'message' => ['chat' => ['id' => 999001], 'message_id' => 10],
                'data' => 'paid_wd_' . $wd->id,
            ],
        ]);

        // Should set state to pay_with_proof
        $cachedState = Cache::get('tg_action_999001');
        $this->assertNotNull($cachedState);
        $this->assertEquals('pay_with_proof', $cachedState['action']);
        $this->assertEquals($wd->id, $cachedState['withdrawal_id']);

        // Should send simple prompt message with 'paid_direct_' inline button without emojis
        Http::assertSent(function ($req) use ($wd) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            $markup = $data['reply_markup'] ?? '';

            return str_contains($text, 'Tolong kirimkan gambar langsung di chat untuk Zaenal Alfian')
                && str_contains($markup, 'paid_direct_' . $wd->id);
        });
    }

    public function test_owner_can_pay_direct_without_proof(): void
    {
        $wd = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 50000,
            'total_amount' => 50000,
            'approved_total_amount' => 50000,
            'status' => 'approved',
            'transfer_proof' => null,
        ]);

        Cache::put('tg_action_999001', [
            'action' => 'pay_with_proof',
            'withdrawal_id' => $wd->id,
        ], 600);

        TelegramBotHandler::handleUpdate([
            'update_id' => 2,
            'callback_query' => [
                'id' => 'cb_paid_direct_1',
                'from' => ['id' => 999001, 'username' => 'owner_user'],
                'message' => ['chat' => ['id' => 999001], 'message_id' => 11],
                'data' => 'paid_direct_' . $wd->id,
            ],
        ]);

        $wd->refresh();
        $this->assertEquals('paid', $wd->status);
        $this->assertNull($wd->transfer_proof);
        $this->assertNull(Cache::get('tg_action_999001'));
    }

    public function test_owner_uploads_photo_in_pay_with_proof_state(): void
    {
        $wd = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 50000,
            'total_amount' => 50000,
            'approved_total_amount' => 50000,
            'status' => 'approved',
            'transfer_proof' => null,
        ]);

        Cache::put('tg_action_999001', [
            'action' => 'pay_with_proof',
            'withdrawal_id' => $wd->id,
        ], 600);

        // Owner sends photo message
        TelegramBotHandler::handleUpdate([
            'update_id' => 3,
            'message' => [
                'message_id' => 12,
                'from' => ['id' => 999001, 'first_name' => 'Owner', 'username' => 'owner_user'],
                'chat' => ['id' => 999001, 'type' => 'private'],
                'photo' => [
                    ['file_id' => 'thumb_1', 'file_size' => 100],
                    ['file_id' => 'photo_file_123', 'file_size' => 5000],
                ],
            ],
        ]);

        $wd->refresh();
        $this->assertEquals('paid', $wd->status);
        $this->assertNotNull($wd->transfer_proof);
        $this->assertStringStartsWith('syirkah/proofs/', $wd->transfer_proof);

        // Storage fake should have the file
        Storage::disk('public')->assertExists($wd->transfer_proof);

        // State should be cleared
        $this->assertNull(Cache::get('tg_action_999001'));

        // Notification confirms payment with proof
        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'Bukti transfer berhasil diupload')
                && str_contains($text, 'PAID');
        });
    }

    public function test_uploadbukti_command_and_callback_only_lists_withdrawals_without_proof(): void
    {
        // WD 1: Approved, no proof -> MUST BE LISTED
        $wd1 = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 25000,
            'total_amount' => 25000,
            'approved_total_amount' => 25000,
            'status' => 'approved',
            'transfer_proof' => null,
        ]);

        // WD 2: Paid, no proof -> MUST BE LISTED
        $wd2 = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 35000,
            'total_amount' => 35000,
            'approved_total_amount' => 35000,
            'status' => 'paid',
            'transfer_proof' => '',
        ]);

        // WD 3: Paid, HAS PROOF -> MUST NOT BE LISTED!
        $wd3 = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 45000,
            'total_amount' => 45000,
            'approved_total_amount' => 45000,
            'status' => 'paid',
            'transfer_proof' => 'syirkah/proofs/existing_receipt.jpg',
        ]);

        // WD 4: Pending, no proof -> MUST NOT BE LISTED (only approved & paid)
        $wd4 = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 15000,
            'total_amount' => 15000,
            'status' => 'pending',
            'transfer_proof' => null,
        ]);

        // 1. Owner triggers command /uploadbukti
        TelegramBotHandler::handleUpdate([
            'update_id' => 4,
            'message' => [
                'message_id' => 13,
                'from' => ['id' => 999001, 'first_name' => 'Owner', 'username' => 'owner_user'],
                'chat' => ['id' => 999001, 'type' => 'private'],
                'text' => '/uploadbukti',
            ],
        ]);

        Http::assertSent(function ($req) use ($wd1, $wd2, $wd3, $wd4) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            $markup = $data['reply_markup'] ?? '';

            return str_contains($text, 'Silakan pilih pengajuan untuk upload bukti transfer')
                && str_contains($text, 'Rp 25.000')
                && str_contains($text, 'Rp 35.000')
                && !str_contains($text, 'Rp 45.000') // WD 3 excluded because it has proof
                && !str_contains($text, 'Rp 15.000') // WD 4 excluded because pending
                && str_contains($markup, 'select_proof_wd_' . $wd1->id)
                && str_contains($markup, 'select_proof_wd_' . $wd2->id)
                && !str_contains($markup, 'select_proof_wd_' . $wd3->id)
                && !str_contains($markup, 'select_proof_wd_' . $wd4->id);
        });

        // 2. Owner triggers via callback query 'cmd_upload_bukti'
        TelegramBotHandler::handleUpdate([
            'update_id' => 5,
            'callback_query' => [
                'id' => 'cb_upload_bukti_1',
                'from' => ['id' => 999001, 'username' => 'owner_user'],
                'message' => ['chat' => ['id' => 999001], 'message_id' => 14],
                'data' => 'cmd_upload_bukti',
            ],
        ]);

        Http::assertSent(function ($req) use ($wd1, $wd2, $wd3) {
            $data = $req->data();
            $markup = $data['reply_markup'] ?? '';

            return str_contains($markup, 'select_proof_wd_' . $wd1->id)
                && str_contains($markup, 'select_proof_wd_' . $wd2->id)
                && !str_contains($markup, 'select_proof_wd_' . $wd3->id);
        });
    }

    public function test_uploadbukti_with_single_candidate_immediately_prompts_to_send_photo(): void
    {
        $wd = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 25000,
            'total_amount' => 25000,
            'approved_total_amount' => 25000,
            'status' => 'approved',
            'transfer_proof' => null,
        ]);

        TelegramBotHandler::handleUpdate([
            'update_id' => 99,
            'message' => [
                'message_id' => 99,
                'from' => ['id' => 999001, 'first_name' => 'Owner', 'username' => 'owner_user'],
                'chat' => ['id' => 999001, 'type' => 'private'],
                'text' => '/uploadbukti',
            ],
        ]);

        // State is immediately set for this single candidate
        $state = Cache::get('tg_action_999001');
        $this->assertNotNull($state);
        $this->assertEquals('upload_proof_only', $state['action']);
        $this->assertEquals($wd->id, $state['withdrawal_id']);

        // Assert direct simple prompt without emojis
        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'Tolong kirimkan gambar langsung di chat untuk Zaenal Alfian');
        });
    }

    public function test_owner_selects_withdrawal_from_list_and_uploads_proof(): void
    {
        $wd = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 60000,
            'total_amount' => 60000,
            'approved_total_amount' => 60000,
            'status' => 'paid',
            'transfer_proof' => null,
        ]);

        // Step 1: Owner selects from list
        TelegramBotHandler::handleUpdate([
            'update_id' => 6,
            'callback_query' => [
                'id' => 'cb_sel_proof_1',
                'from' => ['id' => 999001, 'username' => 'owner_user'],
                'message' => ['chat' => ['id' => 999001], 'message_id' => 15],
                'data' => 'select_proof_wd_' . $wd->id,
            ],
        ]);

        $state = Cache::get('tg_action_999001');
        $this->assertNotNull($state);
        $this->assertEquals('upload_proof_only', $state['action']);
        $this->assertEquals($wd->id, $state['withdrawal_id']);

        // Assert bot sends simple guidance message without emojis
        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'Tolong kirimkan gambar langsung di chat untuk Zaenal Alfian');
        });

        // Step 2: Owner sends photo
        TelegramBotHandler::handleUpdate([
            'update_id' => 7,
            'message' => [
                'message_id' => 16,
                'from' => ['id' => 999001, 'first_name' => 'Owner', 'username' => 'owner_user'],
                'chat' => ['id' => 999001, 'type' => 'private'],
                'photo' => [
                    ['file_id' => 'photo_file_123', 'file_size' => 5000],
                ],
            ],
        ]);

        $wd->refresh();
        $this->assertEquals('paid', $wd->status);
        $this->assertNotNull($wd->transfer_proof);
        Storage::disk('public')->assertExists($wd->transfer_proof);
        $this->assertNull(Cache::get('tg_action_999001'));
    }

    public function test_spontaneous_photo_upload_with_single_candidate(): void
    {
        $wd = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 75000,
            'total_amount' => 75000,
            'approved_total_amount' => 75000,
            'status' => 'approved',
            'transfer_proof' => null,
        ]);

        // Owner sends photo directly without prior command: auto-assigned to the single candidate
        TelegramBotHandler::handleUpdate([
            'update_id' => 8,
            'message' => [
                'message_id' => 17,
                'from' => ['id' => 999001, 'first_name' => 'Owner', 'username' => 'owner_user'],
                'chat' => ['id' => 999001, 'type' => 'private'],
                'photo' => [
                    ['file_id' => 'photo_file_123', 'file_size' => 5000],
                ],
            ],
        ]);

        $wd->refresh();
        $this->assertEquals('paid', $wd->status);
        $this->assertNotNull($wd->transfer_proof);
        Storage::disk('public')->assertExists($wd->transfer_proof);

        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'Bukti transfer berhasil diupload untuk Zaenal Alfian. Pengajuan telah ditandai selesai (PAID).');
        });
    }

    public function test_spontaneous_photo_upload_with_multiple_candidates(): void
    {
        $wd1 = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 10000,
            'total_amount' => 10000,
            'approved_total_amount' => 10000,
            'status' => 'approved',
            'transfer_proof' => null,
        ]);

        $wd2 = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 20000,
            'total_amount' => 20000,
            'approved_total_amount' => 20000,
            'status' => 'paid',
            'transfer_proof' => null,
        ]);

        // Owner sends photo
        TelegramBotHandler::handleUpdate([
            'update_id' => 10,
            'message' => [
                'message_id' => 19,
                'from' => ['id' => 999001, 'first_name' => 'Owner', 'username' => 'owner_user'],
                'chat' => ['id' => 999001, 'type' => 'private'],
                'photo' => [
                    ['file_id' => 'photo_file_123', 'file_size' => 5000],
                ],
            ],
        ]);

        // Should present choices assign_temp_proof_
        Http::assertSent(function ($req) use ($wd1, $wd2) {
            $data = $req->data();
            $markup = $data['reply_markup'] ?? '';

            return str_contains($markup, 'assign_temp_proof_' . $wd1->id)
                && str_contains($markup, 'assign_temp_proof_' . $wd2->id);
        });

        // Owner selects wd2
        TelegramBotHandler::handleUpdate([
            'update_id' => 11,
            'callback_query' => [
                'id' => 'cb_assign_2',
                'from' => ['id' => 999001, 'username' => 'owner_user'],
                'message' => ['chat' => ['id' => 999001], 'message_id' => 20],
                'data' => 'assign_temp_proof_' . $wd2->id,
            ],
        ]);

        $wd2->refresh();
        $this->assertEquals('paid', $wd2->status);
        $this->assertNotNull($wd2->transfer_proof);
        Storage::disk('public')->assertExists($wd2->transfer_proof);
    }

    public function test_non_owner_forbidden_from_uploading_proof_and_accessing_uploadbukti(): void
    {
        // 1. Regular user tries /uploadbukti
        TelegramBotHandler::handleUpdate([
            'update_id' => 12,
            'message' => [
                'message_id' => 21,
                'from' => ['id' => 777001, 'first_name' => 'Zaenal', 'username' => 'zaenal_emp'],
                'chat' => ['id' => 777001, 'type' => 'private'],
                'text' => '/uploadbukti',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return str_contains(strtolower($data['text'] ?? ''), 'akses ditolak')
                && str_contains($data['text'] ?? '', 'Owner');
        });

        // 2. Admin division tries /uploadbukti
        TelegramBotHandler::handleUpdate([
            'update_id' => 13,
            'message' => [
                'message_id' => 22,
                'from' => ['id' => 888001, 'first_name' => 'Admin', 'username' => 'admin_div'],
                'chat' => ['id' => 888001, 'type' => 'private'],
                'text' => '/uploadbukti',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return str_contains(strtolower($data['text'] ?? ''), 'akses ditolak')
                && str_contains($data['text'] ?? '', 'Owner');
        });

        // 3. Admin division sends unsolicited photo
        TelegramBotHandler::handleUpdate([
            'update_id' => 14,
            'message' => [
                'message_id' => 23,
                'from' => ['id' => 888001, 'first_name' => 'Admin', 'username' => 'admin_div'],
                'chat' => ['id' => 888001, 'type' => 'private'],
                'photo' => [
                    ['file_id' => 'photo_file_123', 'file_size' => 5000],
                ],
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return str_contains(strtolower($data['text'] ?? ''), 'akses ditolak')
                && str_contains($data['text'] ?? '', 'Owner');
        });
    }
}
