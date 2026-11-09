<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\TransferDelivery;
use App\Models\User;
use App\Services\SavingTransactionService;
use App\Services\SyirkahTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyirkahTransferIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected User $employee;
    protected Saving $saving;
    protected string $transferUrl = 'https://wick-unlined-gooey.ngrok-free.dev/webhook/syirkah-transfer';
    protected string $apiKey = 'test-secret-key-12345';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.syirkah.transfer_url', $this->transferUrl);
        Config::set('services.syirkah.transfer_secret', $this->apiKey);
        Config::set('services.telegram.bot_token', 'fake-bot-token');
        Config::set('services.telegram.owner_chat_id', '99887766');

        $this->owner = User::factory()->create([
            'name' => 'Fathi Bawazier',
            'group' => 'owner',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Syirkah Admin',
            'group' => 'syirkah',
            'status' => 'active',
        ]);

        $this->employee = User::factory()->create([
            'name' => 'Rizky Darmawan',
            'group' => 'user',
            'status' => 'active',
        ]);

        PaymentMethod::create([
            'user_id' => $this->employee->id,
            'payment_name' => 'BCA',
            'bank_account' => '2313323129',
            'account_name' => 'Rizky Darmawan',
        ]);

        $this->saving = Saving::create([
            'savings_name' => 'Syirkah Penuh',
            'mandatory_amount' => 50000,
        ]);

        SavingTransaction::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'transaction_type' => 'deposit',
            'amount' => 1000000,
            'status' => 'approved',
            'transaction_date' => now(),
        ]);
    }

    public function test_owner_approval_triggers_transfer_request_and_notifies_owner_chatbot_on_success()
    {
        Http::fake([
            $this->transferUrl => Http::response([
                'status' => 'diterima',
                'pesan' => 'Antrean transfer berhasil dibuat',
                'id_pengajuan' => 'SYK-1',
            ], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'withdrawal_type' => 'full',
            'mandatory_amount' => 50000,
            'secondary_amount' => 100000,
            'total_amount' => 150000,
            'reason' => 'Pinjaman - perbaikan HP',
            'status' => 'pending',
            'request_date' => now(),
        ]);

        SavingTransactionService::approveByOwnerWithdrawalRequest(
            $withdrawal->id,
            $this->owner->id,
            150000,
            'Disetujui untuk dicairkan'
        );

        $withdrawal->refresh();
        $this->assertEquals('approved', $withdrawal->status);
        $this->assertEquals($this->owner->id, $withdrawal->owner_approved_by);

        // Verify HTTP request sent to transfer endpoint
        Http::assertSent(function ($request) use ($withdrawal) {
            if ($request->url() === $this->transferUrl) {
                $data = $request->data();
                return $request->hasHeader('X-Syirkah-Kunci', $this->apiKey)
                    && $request->hasHeader('ngrok-skip-browser-warning', '1')
                    && ($data['id_pengajuan'] ?? '') === 'SYK-' . $withdrawal->id
                    && ($data['nama'] ?? '') === 'Rizky Darmawan'
                    && ($data['bank'] ?? '') === 'BCA'
                    && ($data['norek'] ?? '') === '2313323129'
                    && ($data['nominal'] ?? 0) === 150000
                    && ($data['keperluan'] ?? '') === 'Pinjaman - perbaikan HP';
            }
            return false;
        });

        // Verify TransferDelivery record
        $delivery = TransferDelivery::where('application_id', $withdrawal->id)->first();
        $this->assertNotNull($delivery);
        $this->assertEquals(TransferDelivery::STATUS_SENT, $delivery->delivery_status);
        $this->assertEquals(200, $delivery->last_http_status);
        $this->assertNotNull($delivery->sent_at);

        // Verify Telegram chatbot message was sent to Owner
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), 'api.telegram.org')) {
                $text = $request['text'] ?? '';
                return str_contains($text, 'Pengajuan Syirkah Rizky Darmawan - 2313323129 BCA dengan nominal Rp 150.000 siap dibayar');
            }
            return false;
        });
    }

    public function test_non_owner_approval_does_not_trigger_transfer_delivery()
    {
        Http::fake();

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'total_amount' => 200000,
            'reason' => 'Keperluan mendesak',
            'status' => 'pending',
            'request_date' => now(),
        ]);

        $canTrigger = SyirkahTransferService::canTrigger($withdrawal, $this->admin);
        $this->assertFalse($canTrigger);

        Http::assertNothingSent();
        $this->assertEquals(0, TransferDelivery::count());
    }

    public function test_testing_mode_enforces_one_thousand_rupiah_and_testing_context()
    {
        Http::fake([
            $this->transferUrl => Http::response([
                'status' => 'diterima',
                'pesan' => 'Antrean transfer uji berhasil',
                'id_pengajuan' => 'UJI-99',
            ], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'total_amount' => 5000000,
            'approved_total_amount' => 5000000,
            'reason' => 'Renovasi rumah',
            'status' => 'approved',
            'owner_approved_by' => $this->owner->id,
            'request_date' => now(),
        ]);

        $delivery = SyirkahTransferService::send($withdrawal, true);

        $this->assertEquals(TransferDelivery::STATUS_SENT, $delivery->delivery_status);
        $this->assertTrue($delivery->is_test);

        Http::assertSent(function ($request) use ($withdrawal) {
            if ($request->url() === $this->transferUrl) {
                $data = $request->data();
                return ($data['id_pengajuan'] ?? '') === 'UJI-' . $withdrawal->id
                    && ($data['nominal'] ?? 0) === 1000
                    && ($data['keperluan'] ?? '') === 'TESTING SAJA: Uji integrasi Syirkah';
            }
            return false;
        });
    }

    public function test_http_400_marks_delivery_as_failed_validation_without_retry()
    {
        Http::fake([
            $this->transferUrl => Http::response([
                'status' => 'gagal',
                'error' => 'Validasi norek gagal',
            ], 400),
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'total_amount' => 100000,
            'approved_total_amount' => 100000,
            'reason' => 'Tes validasi',
            'status' => 'approved',
            'owner_approved_by' => $this->owner->id,
            'request_date' => now(),
        ]);

        $delivery = SyirkahTransferService::send($withdrawal);

        $this->assertEquals(TransferDelivery::STATUS_FAILED_VALIDATION, $delivery->delivery_status);
        $this->assertEquals(400, $delivery->last_http_status);
        $this->assertNull($delivery->next_retry_at);
    }

    public function test_http_401_marks_delivery_as_failed_auth_without_retry()
    {
        Http::fake([
            $this->transferUrl => Http::response([
                'status' => 'gagal',
                'error' => 'Akses ditolak: X-Syirkah-Kunci tidak cocok',
            ], 401),
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'total_amount' => 100000,
            'approved_total_amount' => 100000,
            'reason' => 'Tes auth',
            'status' => 'approved',
            'owner_approved_by' => $this->owner->id,
            'request_date' => now(),
        ]);

        $delivery = SyirkahTransferService::send($withdrawal);

        $this->assertEquals(TransferDelivery::STATUS_FAILED_AUTH, $delivery->delivery_status);
        $this->assertEquals(401, $delivery->last_http_status);
        $this->assertNull($delivery->next_retry_at);
    }

    public function test_http_404_or_500_schedules_backoff_retry()
    {
        Http::fake([
            $this->transferUrl => Http::response([
                'message' => 'The requested webhook POST syirkah-transfer is not registered.',
            ], 404),
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'total_amount' => 100000,
            'approved_total_amount' => 100000,
            'reason' => 'Tes retry backoff',
            'status' => 'approved',
            'owner_approved_by' => $this->owner->id,
            'request_date' => now(),
        ]);

        $delivery = SyirkahTransferService::send($withdrawal);

        $this->assertEquals(TransferDelivery::STATUS_RETRYING, $delivery->delivery_status);
        $this->assertEquals(404, $delivery->last_http_status);
        $this->assertEquals(1, $delivery->retry_count);
        $this->assertNotNull($delivery->next_retry_at);
    }

    public function test_idempotency_prevents_duplicate_http_transfers_when_already_sent()
    {
        Http::fake([
            $this->transferUrl => Http::response(['status' => 'diterima'], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $withdrawal = SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'total_amount' => 100000,
            'approved_total_amount' => 100000,
            'reason' => 'Tes idempotensi',
            'status' => 'approved',
            'owner_approved_by' => $this->owner->id,
            'request_date' => now(),
        ]);

        // First send
        $firstDelivery = SyirkahTransferService::send($withdrawal);
        $this->assertEquals(TransferDelivery::STATUS_SENT, $firstDelivery->delivery_status);

        // Reset fake recorder
        Http::fake([
            $this->transferUrl => Http::response(['status' => 'diterima'], 200),
        ]);

        // Second send should be skipped
        $secondDelivery = SyirkahTransferService::send($withdrawal);
        $this->assertEquals(TransferDelivery::STATUS_SENT, $secondDelivery->delivery_status);

        Http::assertNotSent(function ($request) {
            return $request->url() === $this->transferUrl;
        });
    }

    public function test_process_transfers_artisan_command_processes_queued_withdrawals()
    {
        Http::fake([
            $this->transferUrl => Http::response(['status' => 'diterima'], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        SavingWithdrawal::create([
            'user_id' => $this->employee->id,
            'savings_id' => $this->saving->id,
            'total_amount' => 250000,
            'approved_total_amount' => 250000,
            'reason' => 'Antrean 1',
            'status' => 'approved',
            'owner_approved_by' => $this->owner->id,
            'request_date' => now(),
        ]);

        $this->artisan('syirkah:process-transfers')
            ->expectsOutputToContain('Ditemukan: 1, Diproses: 1, Sukses: 1')
            ->assertSuccessful();

        $this->assertEquals(1, TransferDelivery::where('delivery_status', TransferDelivery::STATUS_SENT)->count());
    }
}
