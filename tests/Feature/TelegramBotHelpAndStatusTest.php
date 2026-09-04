<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use App\Services\TelegramBotHandler;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotHelpAndStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);
    }

    public function test_help_command_for_owner()
    {
        $owner = User::factory()->create([
            'name' => 'Owner Test',
            'group' => 'owner',
            'chat_code' => '9998881',
            'telegram' => '@ownertest',
        ]);

        $update = [
            'update_id' => 1001,
            'message' => [
                'message_id' => 501,
                'from' => [
                    'id' => 9998881,
                    'is_bot' => false,
                    'first_name' => 'Owner',
                    'username' => 'ownertest',
                ],
                'chat' => [
                    'id' => 9998881,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/help',
            ],
        ];

        TelegramBotHandler::handleUpdate($update);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'PANDUAN OPERASIONAL BOT — OWNER & FINANCE')
                && str_contains($body['text'] ?? '', 'ALUR KERJA OWNER (3 TAHAP)')
                && str_contains($body['reply_markup'] ?? '', 'cmd_pembayaran')
                && str_contains($body['reply_markup'] ?? '', 'cmd_pending');
        });
    }

    public function test_help_command_for_admin_division()
    {
        $division = Division::create(['name' => 'Percetakan & Offset']);
        $admin = User::factory()->create([
            'name' => 'Manager Cetak',
            'group' => 'admin',
            'division_id' => $division->id,
            'chat_code' => '9998882',
            'telegram' => '@admincetak',
        ]);

        $update = [
            'update_id' => 1002,
            'message' => [
                'message_id' => 502,
                'from' => [
                    'id' => 9998882,
                    'is_bot' => false,
                    'first_name' => 'Manager',
                    'username' => 'admincetak',
                ],
                'chat' => [
                    'id' => 9998882,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/help',
            ],
        ];

        TelegramBotHandler::handleUpdate($update);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'PANDUAN OPERASIONAL BOT — MANAJER DIVISI')
                && str_contains($body['text'] ?? '', 'Percetakan &amp; Offset')
                && str_contains($body['reply_markup'] ?? '', 'cmd_pending');
        });
    }

    public function test_status_command_returns_system_and_account_diagnostics()
    {
        $user = User::factory()->create([
            'name' => 'Karyawan Syirkah',
            'group' => 'user',
            'nip' => 'KRY-00123',
            'chat_code' => '9998883',
            'telegram' => '@karyawan123',
        ]);

        $update = [
            'update_id' => 1003,
            'message' => [
                'message_id' => 503,
                'from' => [
                    'id' => 9998883,
                    'is_bot' => false,
                    'first_name' => 'Karyawan',
                    'username' => 'karyawan123',
                ],
                'chat' => [
                    'id' => 9998883,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/status',
            ],
        ];

        TelegramBotHandler::handleUpdate($update);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'STATUS KONEKSI & LAYANAN ABSENSICIPTA')
                && str_contains($body['text'] ?? '', 'Database')
                && str_contains($body['text'] ?? '', 'Karyawan Syirkah')
                && str_contains($body['text'] ?? '', 'KRY-00123')
                && str_contains($body['reply_markup'] ?? '', 'refresh_status');
        });
    }

    public function test_callbacks_for_help_and_status()
    {
        $owner = User::factory()->create([
            'name' => 'Owner Callback Test',
            'group' => 'owner',
            'chat_code' => '9998884',
            'telegram' => '@owner4',
        ]);

        // Callback cmd_status
        $statusCallback = [
            'id' => 'cb_status_1',
            'from' => [
                'id' => 9998884,
                'username' => 'owner4',
                'first_name' => 'Owner',
            ],
            'message' => [
                'message_id' => 701,
                'chat' => ['id' => 9998884],
            ],
            'data' => 'cmd_status',
        ];

        TelegramBotHandler::handleUpdate(['callback_query' => $statusCallback]);

        // Callback refresh_status
        $refreshCallback = [
            'id' => 'cb_refresh_1',
            'from' => [
                'id' => 9998884,
                'username' => 'owner4',
                'first_name' => 'Owner',
            ],
            'message' => [
                'message_id' => 701,
                'chat' => ['id' => 9998884],
            ],
            'data' => 'refresh_status',
        ];

        TelegramBotHandler::handleUpdate(['callback_query' => $refreshCallback]);

        // Callback cmd_help
        $helpCallback = [
            'id' => 'cb_help_1',
            'from' => [
                'id' => 9998884,
                'username' => 'owner4',
                'first_name' => 'Owner',
            ],
            'message' => [
                'message_id' => 701,
                'chat' => ['id' => 9998884],
            ],
            'data' => 'cmd_help',
        ];

        TelegramBotHandler::handleUpdate(['callback_query' => $helpCallback]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'answerCallbackQuery')
                || str_contains($request->url(), 'sendMessage')
                || str_contains($request->url(), 'editMessageText');
        });
    }

    public function test_set_my_commands_registration()
    {
        config(['services.telegram.bot_token' => 'dummy_token_123']);

        $res = TelegramNotificationService::setMyCommands();
        $this->assertTrue($res);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'setMyCommands')
                && isset($request->data()['commands']);
        });
    }
}
