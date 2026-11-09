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
                && str_contains($data['text'], 'CG Bank transfer Request')
                && str_contains($data['text'], 'CetakiaBot')
                && $data['parse_mode'] === 'HTML';
        });

        // Verify it was persisted to cache
        $this->assertEquals($targetGroupId, Cache::get('telegram_bank_transfer_group_id'));
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
