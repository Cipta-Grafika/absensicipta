<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\User;
use App\Services\TelegramBotHandler;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotSecurityScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]], 200),
        ]);
        config(['services.telegram.bot_token' => 'dummy_test_token']);
    }

    public function test_guest_cannot_access_sensitive_commands()
    {
        $guestChatId = 987654321;

        // 1. Guest tries /saldo
        TelegramBotHandler::handleUpdate([
            'update_id' => 101,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => $guestChatId, 'first_name' => 'Stranger', 'username' => 'stranger'],
                'chat' => ['id' => $guestChatId, 'type' => 'private'],
                'text' => '/saldo',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return str_contains($data['text'] ?? '', 'AKSES DITOLAK — AKUN BELUM TERDAFTAR')
                && !str_contains($data['text'] ?? '', 'Total Saldo Wajib');
        });

        // 2. Guest tries /pengajuan
        TelegramBotHandler::handleUpdate([
            'update_id' => 102,
            'message' => [
                'message_id' => 2,
                'from' => ['id' => $guestChatId, 'first_name' => 'Stranger', 'username' => 'stranger'],
                'chat' => ['id' => $guestChatId, 'type' => 'private'],
                'text' => '/pengajuan',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return str_contains($data['text'] ?? '', 'AKSES DITOLAK — AKUN BELUM TERDAFTAR');
        });

        // 3. Guest tries /pembayaran
        TelegramBotHandler::handleUpdate([
            'update_id' => 103,
            'message' => [
                'message_id' => 3,
                'from' => ['id' => $guestChatId, 'first_name' => 'Stranger', 'username' => 'stranger'],
                'chat' => ['id' => $guestChatId, 'type' => 'private'],
                'text' => '/pembayaran',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return str_contains($data['text'] ?? '', 'AKSES DITOLAK — AKUN BELUM TERDAFTAR')
                && !str_contains($data['text'] ?? '', 'ANTREAN PEMBAYARAN');
        });
    }

    public function test_guest_welcome_message_has_no_sensitive_buttons()
    {
        $guestChatId = 987654322;

        TelegramBotHandler::handleUpdate([
            'update_id' => 104,
            'message' => [
                'message_id' => 4,
                'from' => ['id' => $guestChatId, 'first_name' => 'Tamu', 'username' => 'tamu_bot'],
                'chat' => ['id' => $guestChatId, 'type' => 'private'],
                'text' => '/start',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            $replyMarkup = $data['reply_markup'] ?? '';

            return str_contains($text, 'STATUS: AKUN BELUM TERHUBUNG')
                && str_contains($replyMarkup, 'cmd_id')
                && str_contains($replyMarkup, 'cmd_status')
                && !str_contains($replyMarkup, 'cmd_pembayaran')
                && !str_contains($replyMarkup, 'cmd_saldo')
                && !str_contains($replyMarkup, 'cmd_pending');
        });
    }

    public function test_guest_callback_query_access_denied_for_vital_actions()
    {
        $guestChatId = 987654323;

        // Guest clicks cmd_saldo
        TelegramBotHandler::handleUpdate([
            'update_id' => 105,
            'callback_query' => [
                'id' => 'cb_guest_1',
                'from' => ['id' => $guestChatId, 'username' => 'unregistered'],
                'message' => ['chat' => ['id' => $guestChatId], 'message_id' => 11],
                'data' => 'cmd_saldo',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return ($data['callback_query_id'] ?? '') === 'cb_guest_1'
                && str_contains($data['text'] ?? '', 'Akses Ditolak');
        });

        // Guest clicks cmd_pembayaran
        TelegramBotHandler::handleUpdate([
            'update_id' => 106,
            'callback_query' => [
                'id' => 'cb_guest_2',
                'from' => ['id' => $guestChatId, 'username' => 'unregistered'],
                'message' => ['chat' => ['id' => $guestChatId], 'message_id' => 12],
                'data' => 'cmd_pembayaran',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return ($data['callback_query_id'] ?? '') === 'cb_guest_2'
                && str_contains($data['text'] ?? '', 'Akses Ditolak');
        });
    }

    public function test_notification_sent_only_to_employee_division_admin()
    {
        $div1 = Division::create(['name' => 'Divisi Desain']);
        $div2 = Division::create(['name' => 'Divisi Finishing']);

        $adminDiv1 = User::factory()->create([
            'name' => 'Admin Desain',
            'group' => 'admin',
            'division_id' => $div1->id,
            'chat_code' => '111001',
        ]);

        $adminDiv2 = User::factory()->create([
            'name' => 'Admin Finishing',
            'group' => 'admin',
            'division_id' => $div2->id,
            'chat_code' => '222002',
        ]);

        $empDiv1 = User::factory()->create([
            'name' => 'Designer Budi',
            'group' => 'user',
            'division_id' => $div1->id,
            'chat_code' => '555001',
        ]);

        $saving = Saving::create([
            'savings_name' => 'Syirkah Umum',
            'mandatory_amount' => 50000,
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $empDiv1->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'mandatory',
            'mandatory_amount' => 100000,
            'secondary_amount' => 0,
            'total_amount' => 100000,
            'status' => 'pending',
            'reason' => 'Perlu biaya pribadi',
        ]);

        TelegramNotificationService::notifyPendingWithdrawal($withdrawal);

        // Assert notification was sent to Admin Divisi 1 (111001)
        Http::assertSent(function ($req) {
            $data = $req->data();
            return ($data['chat_id'] ?? '') == '111001'
                && str_contains($data['text'] ?? '', 'Designer Budi')
                && str_contains($data['text'] ?? '', 'Divisi Desain');
        });

        // Assert notification was NEVER sent to Admin Divisi 2 (222002)
        Http::assertNotSent(function ($req) {
            $data = $req->data();
            return ($data['chat_id'] ?? '') == '222002';
        });
    }

    public function test_admin_division_only_sees_own_division_in_pengajuan()
    {
        $div1 = Division::create(['name' => 'Divisi Cetak']);
        $div2 = Division::create(['name' => 'Divisi Logistik']);

        $adminDiv1 = User::factory()->create([
            'name' => 'Manager Cetak',
            'group' => 'admin',
            'division_id' => $div1->id,
            'chat_code' => '111002',
        ]);

        $empDiv1 = User::factory()->create([
            'name' => 'Operator Anton',
            'group' => 'user',
            'division_id' => $div1->id,
        ]);

        $empDiv2 = User::factory()->create([
            'name' => 'Driver Doni',
            'group' => 'user',
            'division_id' => $div2->id,
        ]);

        $saving = Saving::create(['savings_name' => 'Syirkah Umum', 'mandatory_amount' => 50000]);

        $wd1 = SavingWithdrawal::create([
            'user_id' => $empDiv1->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 150000,
            'total_amount' => 150000,
            'status' => 'pending',
        ]);

        $wd2 = SavingWithdrawal::create([
            'user_id' => $empDiv2->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 300000,
            'total_amount' => 300000,
            'status' => 'pending',
        ]);

        // Admin Div 1 runs /pengajuan
        TelegramBotHandler::handleUpdate([
            'update_id' => 107,
            'message' => [
                'message_id' => 7,
                'from' => ['id' => 111002, 'first_name' => 'Manager', 'username' => 'mgr_cetak'],
                'chat' => ['id' => 111002, 'type' => 'private'],
                'text' => '/pengajuan',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'Operator Anton')
                && str_contains($text, 'Divisi Cetak')
                && !str_contains($text, 'Driver Doni')
                && !str_contains($text, 'Divisi Logistik');
        });
    }

    public function test_admin_division_only_sees_own_division_balance()
    {
        $div1 = Division::create(['name' => 'Divisi Toko']);
        $div2 = Division::create(['name' => 'Divisi Gudang']);

        $adminDiv1 = User::factory()->create([
            'name' => 'Manager Toko',
            'group' => 'admin',
            'division_id' => $div1->id,
            'chat_code' => '111003',
        ]);

        $empDiv1 = User::factory()->create([
            'name' => 'Kasir Siti',
            'group' => 'user',
            'division_id' => $div1->id,
        ]);

        $empDiv2 = User::factory()->create([
            'name' => 'Staff Rudi',
            'group' => 'user',
            'division_id' => $div2->id,
        ]);

        $saving = Saving::create(['savings_name' => 'Syirkah Umum', 'mandatory_amount' => 50000]);

        // Deposit for Div 1: 250,000
        SavingTransaction::create([
            'user_id' => $empDiv1->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 250000,
            'secondary_amount' => 0,
            'balance_mandatory' => 250000,
            'balance_secondary' => 0,
            'status' => 'approved',
        ]);

        // Deposit for Div 2: 750,000
        SavingTransaction::create([
            'user_id' => $empDiv2->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 750000,
            'secondary_amount' => 0,
            'balance_mandatory' => 750000,
            'balance_secondary' => 0,
            'status' => 'approved',
        ]);

        // Admin Div 1 runs /saldo
        TelegramBotHandler::handleUpdate([
            'update_id' => 108,
            'message' => [
                'message_id' => 8,
                'from' => ['id' => 111003, 'first_name' => 'Manager', 'username' => 'mgr_toko'],
                'chat' => ['id' => 111003, 'type' => 'private'],
                'text' => '/saldo',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'DIVISI DIVISI TOKO')
                && str_contains($text, '250.000')
                && !str_contains($text, '1.000.000')
                && !str_contains($text, '750.000');
        });
    }

    public function test_admin_cannot_approve_or_reject_other_division_withdrawal()
    {
        $div1 = Division::create(['name' => 'Divisi A']);
        $div2 = Division::create(['name' => 'Divisi B']);

        $adminDiv1 = User::factory()->create([
            'name' => 'Admin Divisi A',
            'group' => 'admin',
            'division_id' => $div1->id,
            'chat_code' => '111004',
        ]);

        $empDiv2 = User::factory()->create([
            'name' => 'Employee B',
            'group' => 'user',
            'division_id' => $div2->id,
        ]);

        $saving = Saving::create(['savings_name' => 'Syirkah', 'mandatory_amount' => 50000]);

        $wdDiv2 = SavingWithdrawal::create([
            'user_id' => $empDiv2->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'mandatory',
            'mandatory_amount' => 100000,
            'secondary_amount' => 0,
            'total_amount' => 100000,
            'status' => 'pending',
        ]);

        // Admin Div 1 attempts acc_wd_ on Div 2 withdrawal
        TelegramBotHandler::handleUpdate([
            'update_id' => 109,
            'callback_query' => [
                'id' => 'cb_hack_acc',
                'from' => ['id' => 111004, 'username' => 'admin_a'],
                'message' => ['chat' => ['id' => 111004], 'message_id' => 15],
                'data' => 'acc_wd_' . $wdDiv2->id,
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return ($data['callback_query_id'] ?? '') === 'cb_hack_acc'
                && str_contains($data['text'] ?? '', 'Akses Ditolak');
        });

        $this->assertEquals('pending', $wdDiv2->fresh()->status);
    }

    public function test_admin_cannot_access_payment_queue()
    {
        $div = Division::create(['name' => 'Divisi Umum']);
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'group' => 'admin',
            'division_id' => $div->id,
            'chat_code' => '111005',
        ]);

        TelegramBotHandler::handleUpdate([
            'update_id' => 110,
            'message' => [
                'message_id' => 10,
                'from' => ['id' => 111005, 'first_name' => 'Admin', 'username' => 'admin_u'],
                'chat' => ['id' => 111005, 'type' => 'private'],
                'text' => '/pembayaran',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return str_contains($data['text'] ?? '', 'Akses Terbatas')
                && str_contains($data['text'] ?? '', 'Owner / Bagian Keuangan');
        });
    }

    public function test_regular_user_only_sees_personal_balance_and_requests_with_no_approval_buttons()
    {
        $div = Division::create(['name' => 'Divisi Operasional']);
        $user1 = User::factory()->create([
            'name' => 'User Ahmad',
            'group' => 'user',
            'division_id' => $div->id,
            'chat_code' => '333001',
        ]);

        $user2 = User::factory()->create([
            'name' => 'User Bambang',
            'group' => 'user',
            'division_id' => $div->id,
            'chat_code' => '333002',
        ]);

        $saving = Saving::create(['savings_name' => 'Syirkah Umum', 'mandatory_amount' => 50000]);

        // User 1 deposit: 100,000
        SavingTransaction::create([
            'user_id' => $user1->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 100000,
            'secondary_amount' => 0,
            'balance_mandatory' => 100000,
            'balance_secondary' => 0,
            'status' => 'approved',
        ]);

        // User 2 deposit: 900,000
        SavingTransaction::create([
            'user_id' => $user2->id,
            'savings_id' => $saving->id,
            'transaction_type' => 'deposit',
            'mandatory_amount' => 900000,
            'secondary_amount' => 0,
            'balance_mandatory' => 900000,
            'balance_secondary' => 0,
            'status' => 'approved',
        ]);

        $wd1 = SavingWithdrawal::create([
            'user_id' => $user1->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'mandatory',
            'mandatory_amount' => 50000,
            'secondary_amount' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        // 1. User 1 checks /saldo
        TelegramBotHandler::handleUpdate([
            'update_id' => 111,
            'message' => [
                'message_id' => 11,
                'from' => ['id' => 333001, 'first_name' => 'Ahmad', 'username' => 'ahmad1'],
                'chat' => ['id' => 333001, 'type' => 'private'],
                'text' => '/saldo',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'INFORMASI SALDO SYIRKAH PRIBADI')
                && str_contains($text, 'User Ahmad')
                && str_contains($text, '100.000')
                && !str_contains($text, '1.000.000');
        });

        // 2. User 1 checks /pengajuan
        TelegramBotHandler::handleUpdate([
            'update_id' => 112,
            'message' => [
                'message_id' => 12,
                'from' => ['id' => 333001, 'first_name' => 'Ahmad', 'username' => 'ahmad1'],
                'chat' => ['id' => 333001, 'type' => 'private'],
                'text' => '/pengajuan',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            $markup = $data['reply_markup'] ?? '';
            return str_contains($text, 'STATUS PENGAJUAN PENARIKAN SYIRKAH SAYA')
                && str_contains($text, '50.000')
                && !str_contains($markup, 'acc_wd_')
                && !str_contains($markup, 'rej_wd_');
        });
    }

    public function test_owner_has_global_scope_across_all_divisions_and_exclusive_actions()
    {
        $div1 = Division::create(['name' => 'Divisi Alpha']);
        $div2 = Division::create(['name' => 'Divisi Beta']);

        $owner = User::factory()->create([
            'name' => 'Pak Owner',
            'group' => 'owner',
            'chat_code' => '999001',
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Alpha',
            'group' => 'admin',
            'division_id' => $div1->id,
            'chat_code' => '999002',
        ]);

        $emp1 = User::factory()->create(['name' => 'Worker 1', 'group' => 'user', 'division_id' => $div1->id]);
        $emp2 = User::factory()->create(['name' => 'Worker 2', 'group' => 'user', 'division_id' => $div2->id]);

        $saving = Saving::create(['savings_name' => 'Syirkah', 'mandatory_amount' => 50000]);

        $wd1 = SavingWithdrawal::create([
            'user_id' => $emp1->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 200000,
            'total_amount' => 200000,
            'status' => 'accepted',
        ]);

        $wd2 = SavingWithdrawal::create([
            'user_id' => $emp2->id,
            'savings_id' => $saving->id,
            'withdrawal_type' => 'secondary',
            'mandatory_amount' => 0,
            'secondary_amount' => 300000,
            'total_amount' => 300000,
            'status' => 'pending',
        ]);

        // 1. Owner sees both divisions in /pengajuan
        TelegramBotHandler::handleUpdate([
            'update_id' => 113,
            'message' => [
                'message_id' => 13,
                'from' => ['id' => 999001, 'first_name' => 'Owner', 'username' => 'owner'],
                'chat' => ['id' => 999001, 'type' => 'private'],
                'text' => '/pengajuan',
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            $text = $data['text'] ?? '';
            return str_contains($text, 'GLOBAL / OWNER')
                && str_contains($text, 'Worker 1')
                && str_contains($text, 'Worker 2');
        });

        // 2. Non-owner (Admin) attempts owner_acc_wd_ -> Denied!
        TelegramBotHandler::handleUpdate([
            'update_id' => 114,
            'callback_query' => [
                'id' => 'cb_owner_hack',
                'from' => ['id' => 999002, 'username' => 'admin_alpha'],
                'message' => ['chat' => ['id' => 999002], 'message_id' => 20],
                'data' => 'owner_acc_wd_' . $wd1->id,
            ],
        ]);

        Http::assertSent(function ($req) {
            $data = $req->data();
            return ($data['callback_query_id'] ?? '') === 'cb_owner_hack'
                && str_contains($data['text'] ?? '', 'Akses Ditolak')
                && str_contains($data['text'] ?? '', 'Owner');
        });
    }
}
