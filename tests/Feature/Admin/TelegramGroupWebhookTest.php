<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MasterData\Admin;
use App\Models\User;
use App\Services\TelegramBotHandler;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramGroupWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'group' => 'superadmin',
            'telegram' => '@superadmin',
            'chat_code' => '1909551792',
        ]);

        config(['services.telegram.bot_token' => 'dummy_bot_token_12345']);
    }

    public function test_superadmin_can_view_telegram_tester_button_on_admin_page()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('hr.masters.admin'));

        $response->assertStatus(200);
        $response->assertSee('Test Webhook CetakiaBot (Grup)');
    }

    public function test_telegram_tester_modal_initialization_and_group_detection()
    {
        Cache::forever('telegram_bank_transfer_group_id', '-1009876543210');
        Cache::forever('telegram_known_groups', [
            '-1009876543210' => [
                'id' => '-1009876543210',
                'title' => 'CG Bank transfer Request',
                'type' => 'supergroup',
                'updated_at' => now()->toIso8601String(),
            ]
        ]);

        Livewire::actingAs($this->superAdmin)->test(Admin::class)
            ->dispatch('show-telegram-tester')
            ->assertSet('showTelegramModal', true)
            ->assertSet('telegramTargetGroup', '-1009876543210');
    }

    public function test_tester_strictly_rejects_personal_user_id()
    {
        Livewire::actingAs($this->superAdmin)->test(Admin::class)
            ->set('telegramTargetGroup', '1909551792') // Positive user ID, NOT group
            ->call('sendTelegramGroupTest')
            ->assertSet('telegramTestResult', null)
            ->assertSee('bukan merupakan ID Grup Telegram');
    }

    public function test_tester_successfully_sends_to_group_and_records_precise_request_response()
    {
        $targetGroupId = '-1002345678901';

        Http::fake([
            'https://api.telegram.org/botdummy_bot_token_12345/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 9999,
                    'from' => [
                        'id' => 7633848131,
                        'is_bot' => true,
                        'first_name' => 'CetakiaBot',
                        'username' => 'cetakia_bot',
                    ],
                    'chat' => [
                        'id' => (int) $targetGroupId,
                        'title' => 'CG Bank transfer Request',
                        'type' => 'supergroup',
                    ],
                    'date' => 1712345678,
                    'text' => '🔔 UJI COBA WEBHOOK & NOTIFIKASI CETAKIABOT',
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superAdmin)->test(Admin::class)
            ->set('telegramTargetGroup', $targetGroupId)
            ->set('telegramCustomMessage', 'Pesan uji coba khusus untuk verifikasi grup')
            ->call('sendTelegramGroupTest')
            ->assertSet('telegramTestResult.success', true)
            ->assertSet('telegramTestResult.status_code', 200)
            ->assertSet('telegramTestResult.details.message_id', 9999)
            ->assertSet('telegramTestResult.details.chat_title', 'CG Bank transfer Request')
            ->assertSet('telegramTestResult.details.chat_type', 'supergroup');

        Http::assertSent(function ($request) use ($targetGroupId) {
            $data = $request->data();
            return $data['chat_id'] === $targetGroupId
                && str_contains($data['text'], 'Account Bank :')
                && str_contains($data['text'], 'Transfer Amount: Rp 2.000.000 (Pengajuan: Rp 3.000.000)')
                && str_contains($data['text'], 'Berita: Pesan uji coba khusus untuk verifikasi grup')
                && str_contains($data['text'], '(From Rekening Syirkah)')
                && $data['parse_mode'] === 'HTML';
        });

        // Verify it was persisted to cache
        $this->assertEquals($targetGroupId, Cache::get('telegram_bank_transfer_group_id'));
    }

    public function test_notify_accepted_withdrawal_never_sends_to_group()
    {
        $groupChatId = '-1009887766554';
        Cache::forever('telegram_bank_transfer_group_id', $groupChatId);

        $division = \App\Models\Division::factory()->create(['name' => 'Cipta Corp']);
        $employee = User::factory()->create([
            'name' => 'Nurlinda Meiyuni',
            'nip' => '2308407',
            'division_id' => $division->id,
        ]);

        $owner = User::factory()->create([
            'name' => 'Fathi Bawazier',
            'group' => 'owner',
            'chat_code' => '123456789',
        ]);

        $saving = \App\Models\Saving::create([
            'savings_name' => 'Syirkah Penuh',
            'mandatory_amount' => 50000,
        ]);

        $withdrawal = \App\Models\SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'total_amount' => 3000000,
            'approved_total_amount' => 2000000,
            'status' => 'accepted',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);

        TelegramNotificationService::notifyAcceptedWithdrawal($withdrawal);

        // Verify NO request was sent to the group chat
        Http::assertNotSent(function ($request) use ($groupChatId) {
            return $request->data()['chat_id'] === $groupChatId;
        });

        // Verify request WAS sent to Owner private chat
        Http::assertSent(function ($request) {
            return $request->data()['chat_id'] === '123456789';
        });
    }

    public function test_notify_owner_approved_withdrawal_sends_exact_unpaid_format_to_group()
    {
        $groupChatId = '-1009887766554';
        Cache::forever('telegram_bank_transfer_group_id', $groupChatId);

        $division = \App\Models\Division::factory()->create(['name' => 'Cipta Corp']);
        $employee = User::factory()->create([
            'name' => 'Nurlinda Meiyuni',
            'nip' => '2308407',
            'division_id' => $division->id,
        ]);

        \App\Models\PaymentMethod::create([
            'user_id' => $employee->id,
            'payment_name' => 'BCA',
            'bank_account' => '23346234',
            'account_name' => 'Nurlinda Meiyuni',
        ]);

        $owner = User::factory()->create([
            'name' => 'Fathi Bawazier',
            'group' => 'owner',
        ]);

        $saving = \App\Models\Saving::create([
            'savings_name' => 'Syirkah Penuh',
            'mandatory_amount' => 50000,
        ]);

        $withdrawal = \App\Models\SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'total_amount' => 3000000,
            'approved_total_amount' => 2000000,
            'owner_approved_by' => $owner->id,
            'owner_approved_at' => now(),
            'owner_note' => 'disini masukan description/notes dari owner',
            'reason' => 'notes catatan alasan pengajuan syirkah dari user employee',
            'status' => 'approved',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);

        TelegramNotificationService::notifyOwnerApprovedWithdrawal($withdrawal);

        Http::assertSent(function ($request) use ($groupChatId) {
            $data = $request->data();
            if ($data['chat_id'] !== $groupChatId) {
                return false;
            }

            $text = $data['text'];
            $expected = "Account Bank : 23346234 BCA a.n Nurlinda Meiyuni\n"
                . "Transfer Amount: Rp 2.000.000 (Pengajuan: Rp 3.000.000)\n"
                . "Berita: notes catatan alasan pengajuan syirkah dari user employee\n"
                . "(From Rekening Syirkah)";

            return $text === $expected && empty($data['reply_markup']) && ($data['protect_content'] ?? false) === true;
        });
    }

    public function test_notify_owner_approved_withdrawal_flags_mismatched_bank_account_name()
    {
        $groupChatId = '-1009887766554';
        Cache::forever('telegram_bank_transfer_group_id', $groupChatId);

        $division = \App\Models\Division::factory()->create(['name' => 'Cipta Corp']);
        $employee = User::factory()->create([
            'name' => 'Nurlinda Meiyuni',
            'nip' => '2308407',
            'division_id' => $division->id,
        ]);

        // Mismatched account name
        \App\Models\PaymentMethod::create([
            'user_id' => $employee->id,
            'payment_name' => 'BCA',
            'bank_account' => '23346234',
            'account_name' => 'Budi Santoso',
        ]);

        $owner = User::factory()->create([
            'name' => 'Fathi Bawazier',
            'group' => 'owner',
        ]);

        $saving = \App\Models\Saving::create([
            'savings_name' => 'Syirkah Penuh',
            'mandatory_amount' => 50000,
        ]);

        $withdrawal = \App\Models\SavingWithdrawal::create([
            'user_id' => $employee->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'total_amount' => 2000000,
            'approved_total_amount' => 2000000,
            'owner_approved_by' => $owner->id,
            'owner_approved_at' => now(),
            'status' => 'approved',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);

        TelegramNotificationService::notifyOwnerApprovedWithdrawal($withdrawal);

        Http::assertSent(function ($request) use ($groupChatId) {
            $data = $request->data();
            if ($data['chat_id'] !== $groupChatId) {
                return false;
            }

            $text = $data['text'];
            return str_contains($text, 'Account Bank : 23346234 BCA a.n Budi Santoso (PERHATIAN: Nama rekening berbeda dengan karyawan)')
                && str_contains($text, '(From Rekening Syirkah)');
        });
    }

    public function test_capture_group_info_stores_group_from_webhook_update()
    {
        $groupUpdate = [
            'update_id' => 555,
            'message' => [
                'message_id' => 123,
                'from' => [
                    'id' => 1909551792,
                    'first_name' => 'Fathi',
                    'username' => 'fathi',
                ],
                'chat' => [
                    'id' => -1005544332211,
                    'title' => 'CG Bank transfer Request',
                    'type' => 'supergroup',
                ],
                'text' => '/id',
            ],
        ];

        TelegramBotHandler::captureGroupInfo($groupUpdate);

        $this->assertEquals('-1005544332211', Cache::get('telegram_bank_transfer_group_id'));
        $this->assertEquals('CG Bank transfer Request', Cache::get('telegram_bank_transfer_group_title'));
    }

    public function test_telegram_notification_service_resolves_bank_transfer_group_id()
    {
        Cache::forever('telegram_bank_transfer_group_id', '-1009988776655');

        $this->assertEquals('-1009988776655', TelegramNotificationService::getBankTransferGroupChatId());
    }

    public function test_persistent_storage_resolves_bank_transfer_group_id_when_cache_is_cleared()
    {
        $targetId = '-1007788990011';
        TelegramNotificationService::setBankTransferGroupChatId($targetId, 'CG Bank transfer Request');

        // Simulate cache:clear by wiping cache
        Cache::forget('telegram_bank_transfer_group_id');

        $this->assertEquals($targetId, TelegramNotificationService::getBankTransferGroupChatId());

        // Clean up test file if created
        $file = storage_path('app/telegram_group_config.json');
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
