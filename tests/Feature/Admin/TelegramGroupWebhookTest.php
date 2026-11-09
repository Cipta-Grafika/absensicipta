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
                && str_contains($data['text'], '💵 PENCAIRAN SYIRKAH (UNPAID)')
                && str_contains($data['text'], 'Dana penarikan syirkah siap ditransfer')
                && str_contains($data['text'], 'Status: UNPAID')
                && $data['parse_mode'] === 'HTML';
        });

        // Verify it was persisted to cache
        $this->assertEquals($targetGroupId, Cache::get('telegram_bank_transfer_group_id'));
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
            return str_contains($text, '💵 PENCAIRAN SYIRKAH (UNPAID)')
                && str_contains($text, '━━━━━━━━━━━━━━━━━━━━━━━━━━')
                && str_contains($text, 'Dana penarikan syirkah siap ditransfer / dibayarkan oleh Owner/Finance.')
                && str_contains($text, 'Nama (Name)  : Nurlinda Meiyuni')
                && str_contains($text, 'NIP : 2308407')
                && str_contains($text, 'Divisi    : Cipta Corp')
                && str_contains($text, 'Program   : Syirkah Penuh')
                && str_contains($text, 'Nominal Bayar (Transfer Amount) : Rp 2.000.000 (Pengajuan: Rp 3.000.000)')
                && str_contains($text, 'Dibayar Oleh (Transfer By) : Fathi Bawazier')
                && str_contains($text, 'Waktu Bayar :')
                && str_contains($text, 'Notes : "disini masukan description/notes dari owner"')
                && str_contains($text, 'Status: UNPAID')
                // Strictly ensure no reply markup in group chat
                && empty($data['reply_markup']);
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
}
