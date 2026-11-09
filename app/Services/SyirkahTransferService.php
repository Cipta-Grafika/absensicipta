<?php

namespace App\Services;

use App\Models\SavingWithdrawal;
use App\Models\TransferDelivery;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyirkahTransferService
{
    /**
     * Determine if a withdrawal request is strictly authorized to trigger transfer integration.
     *
     * Mandatory conditions from engineering specification (Section 2.1 & Section 3):
     * 1. status == APPROVED
     * 2. approved_by_role == OWNER
     * 3. approved_by_user_id IS NOT NULL
     */
    public static function canTrigger(SavingWithdrawal $withdrawal, ?User $actor = null): bool
    {
        // 1. Status must be APPROVED
        if ($withdrawal->status !== 'approved') {
            Log::info("SyirkahTransferService: Trigger rejected - status is '{$withdrawal->status}', required 'approved'.", [
                'withdrawal_id' => $withdrawal->id,
            ]);
            return false;
        }

        // 2. approved_by_user_id must be present
        if (empty($withdrawal->owner_approved_by)) {
            Log::warning('SyirkahTransferService: Trigger rejected - owner_approved_by is NULL.', [
                'withdrawal_id' => $withdrawal->id,
            ]);
            return false;
        }

        // 3. Approver must have role OWNER or SUPERADMIN
        $approver = $withdrawal->ownerApprover ?? User::find($withdrawal->owner_approved_by);
        $isAuthorizedApprover = $approver && (
            $approver->group === 'owner' 
            || $approver->isOwner 
            || $approver->group === 'superadmin' 
            || $approver->isSuperadmin
        );
        if (!$isAuthorizedApprover) {
            Log::warning("SyirkahTransferService: Trigger rejected - approver role is '{$approver?->group}', strictly requires 'owner' or 'superadmin'.", [
                'withdrawal_id' => $withdrawal->id,
                'approver_id' => $withdrawal->owner_approved_by,
            ]);
            return false;
        }

        // 4. If an actor is explicitly passed, verify actor role is OWNER or SUPERADMIN
        if ($actor && !in_array($actor->group, ['owner', 'superadmin']) && !$actor->isOwner && !$actor->isSuperadmin) {
            Log::warning("SyirkahTransferService: Trigger rejected - actor role is '{$actor->group}', strictly requires 'owner' or 'superadmin'.", [
                'actor_id' => $actor->id,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Build standard payload according to API contract (Section 8 & Section 9).
     */
    public static function buildPayload(
        SavingWithdrawal $withdrawal,
        bool $isTest = false,
        ?int $customNominal = null,
        ?string $customKeperluan = null
    ): array {
        $withdrawal->loadMissing(['user.paymentMethod']);

        $user = $withdrawal->user;
        $pm = $user?->paymentMethod;

        $prefix = $isTest ? 'UJI-' : 'SYK-';
        $idPengajuan = substr($prefix . $withdrawal->id, 0, 40);

        // Account name from payment method or user name
        $nama = trim($pm?->account_name ?: ($user?->name ?: 'Penerima'));

        // Bank name normalized
        $bank = strtoupper(trim($pm?->payment_name ?: 'BCA'));

        // Normalize account number: digits only
        $norek = preg_replace('/[^0-9]/', '', (string) ($pm?->bank_account ?: ''));
        if (strlen($norek) < 6) {
            $norek = str_pad($norek, 6, '0', STR_PAD_RIGHT);
        }

        // Nominal calculation
        if ($customNominal !== null) {
            $nominal = $customNominal;
        } elseif ($isTest) {
            // Requirement: "apabila melakukan pengujian pastikan kirimkan hanya 1000 rupiah dengan catatan dan konteks TESTING SAJA"
            $nominal = 1000;
        } else {
            $nominal = (int) round($withdrawal->effective_total_amount);
        }

        // Keperluan (max 120 chars)
        if ($customKeperluan !== null) {
            $keperluan = mb_substr($customKeperluan, 0, 120);
        } elseif ($isTest) {
            $keperluan = 'TESTING SAJA: Uji integrasi Syirkah';
        } else {
            $keperluan = mb_substr(trim($withdrawal->reason ?: 'Pencairan Syirkah'), 0, 120);
        }

        return [
            'id_pengajuan' => $idPengajuan,
            'nama' => $nama,
            'bank' => $bank,
            'norek' => $norek,
            'nominal' => $nominal,
            'keperluan' => $keperluan,
        ];
    }

    /**
     * Validate payload fields according to Section 9 specification.
     */
    public static function validatePayload(array $payload, bool $isTest = false): array
    {
        $errors = [];

        // id_pengajuan: regex ^[A-Za-z0-9_-]{1,40}$
        if (empty($payload['id_pengajuan']) || !preg_match('/^[A-Za-z0-9_-]{1,40}$/', $payload['id_pengajuan'])) {
            $errors[] = 'id_pengajuan wajib diisi, alfanumerik/-/_ maksimum 40 karakter.';
        }

        // nama: required
        if (empty($payload['nama']) || strlen(trim($payload['nama'])) < 1) {
            $errors[] = 'nama pemilik rekening wajib diisi.';
        }

        // bank: required
        if (empty($payload['bank']) || strlen(trim($payload['bank'])) < 1) {
            $errors[] = 'nama bank penerima wajib diisi.';
        }

        // norek: 6-20 digits
        if (empty($payload['norek']) || !preg_match('/^[0-9]{6,20}$/', $payload['norek'])) {
            $errors[] = 'nomor rekening (norek) harus berupa 6-20 digit angka.';
        }

        // nominal: integer
        $minNominal = $isTest ? 1000 : 10000;
        $maxNominal = 50000000;
        if (!is_int($payload['nominal']) || $payload['nominal'] < $minNominal || $payload['nominal'] > $maxNominal) {
            $errors[] = "nominal harus berupa integer Rupiah antara Rp " . number_format($minNominal, 0, ',', '.') . " s/d Rp " . number_format($maxNominal, 0, ',', '.') . ".";
        }

        // keperluan: max 120 chars
        if (empty($payload['keperluan']) || mb_strlen($payload['keperluan']) > 120) {
            $errors[] = 'keperluan wajib diisi dan maksimal 120 karakter.';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validasi Payload Transfer Syirkah Gagal: ' . implode(' ', $errors));
        }

        return $payload;
    }

    /**
     * Send or queue transfer request for an owner-approved withdrawal.
     */
    public static function send(
        SavingWithdrawal $withdrawal,
        bool $isTest = false,
        ?int $testNominal = null,
        ?string $testNote = null,
        ?User $actor = null
    ): TransferDelivery {
        $withdrawal->loadMissing(['user.paymentMethod', 'ownerApprover']);

        // Check trigger guard:
        // Real production transfers STRICTLY require status == APPROVED and role == OWNER.
        // Test transfers (isTest == true) are diagnostic checks (Rp 1.000 / TESTING SAJA) permitted for Owner/Superadmin.
        if (!$isTest) {
            if (!self::canTrigger($withdrawal, $actor)) {
                Log::warning("SyirkahTransferService: Cannot trigger transfer for withdrawal {$withdrawal->id}. Authorization conditions not met.");

                return TransferDelivery::firstOrCreate(
                    ['application_id' => $withdrawal->id],
                    [
                        'id_pengajuan' => 'SYK-' . $withdrawal->id,
                        'delivery_status' => TransferDelivery::STATUS_NOT_SENT,
                        'last_error_message' => 'Trigger rejected: HANYA approval oleh role OWNER yang dapat memicu integrasi transfer.',
                    ]
                );
            }
        } else {
            if ($actor && !in_array($actor->group, ['owner', 'superadmin']) && !$actor->isOwner) {
                Log::warning("SyirkahTransferService: Test trigger rejected for actor {$actor->id} with group {$actor->group}.");

                return TransferDelivery::firstOrCreate(
                    ['application_id' => $withdrawal->id],
                    [
                        'id_pengajuan' => 'UJI-' . $withdrawal->id,
                        'delivery_status' => TransferDelivery::STATUS_NOT_SENT,
                        'last_error_message' => 'Trigger rejected: Pengujian hanya dapat dilakukan oleh role OWNER atau SUPERADMIN.',
                    ]
                );
            }
        }

        // Build and validate payload
        $payload = self::buildPayload($withdrawal, $isTest, $testNominal, $testNote);
        self::validatePayload($payload, $isTest);

        $idPengajuan = $payload['id_pengajuan'];

        // Idempotency: Retrieve existing delivery or create new one
        $delivery = TransferDelivery::firstOrNew(['application_id' => $withdrawal->id]);

        // If already successfully sent, do not send duplicate
        if ($delivery->exists && $delivery->delivery_status === TransferDelivery::STATUS_SENT) {
            Log::info("SyirkahTransferService: Withdrawal {$withdrawal->id} has already been sent (id_pengajuan: {$delivery->id_pengajuan}). Stopping duplicate request.");
            return $delivery;
        }

        $delivery->id_pengajuan = $idPengajuan;
        $delivery->delivery_status = TransferDelivery::STATUS_SENDING;
        $delivery->payload = $payload;
        $delivery->last_attempt_at = now();
        $delivery->first_attempt_at = $delivery->first_attempt_at ?: now();
        $delivery->save();

        // Resolve endpoint and secret
        $url = config('services.syirkah.transfer_url', env('SYIRKAH_TRANSFER_URL', 'https://wick-unlined-gooey.ngrok-free.dev/webhook/syirkah-transfer'));
        $secret = config('services.syirkah.transfer_secret', env('API_KEY', env('SYIRKAH_TRANSFER_SECRET')));

        // Masked account number for safe logging (e.g. 2313323129 -> ******3129)
        $maskedNorek = strlen($payload['norek']) > 4
            ? str_repeat('*', strlen($payload['norek']) - 4) . substr($payload['norek'], -4)
            : '****';

        Log::info("SyirkahTransferService: Sending transfer request for {$idPengajuan} to {$url}", [
            'id_pengajuan' => $idPengajuan,
            'nama' => $payload['nama'],
            'bank' => $payload['bank'],
            'norek' => $maskedNorek,
            'nominal' => $payload['nominal'],
        ]);

        try {
            $response = Http::timeout(20)->withHeaders([
                'Content-Type' => 'application/json',
                'X-Syirkah-Kunci' => $secret,
                'ngrok-skip-browser-warning' => '1',
            ])->post($url, $payload);

            $httpStatus = $response->status();
            $responseJson = $response->json();
            $delivery->last_http_status = $httpStatus;
            $delivery->response_body = $responseJson ?: ['raw' => $response->body()];

            // 1. Success cases: HTTP 200 with 'diterima' or 'sudah_ada'
            if ($response->successful()) {
                $statusText = strtolower($responseJson['status'] ?? $responseJson['message'] ?? 'diterima');
                $idLedger = $responseJson['id_ledger'] ?? $responseJson['data']['id_ledger'] ?? null;
                $statusLedger = $responseJson['status_ledger'] ?? $responseJson['data']['status_ledger'] ?? null;

                $delivery->delivery_status = TransferDelivery::STATUS_SENT;
                $delivery->id_ledger = $idLedger;
                $delivery->status_ledger = $statusLedger;
                $delivery->sent_at = now();
                $delivery->last_error_message = null;
                $delivery->next_retry_at = null;
                $delivery->save();

                Log::info("SyirkahTransferService: Transfer request {$idPengajuan} succeeded (HTTP {$httpStatus}, status: {$statusText}).", [
                    'id_ledger' => $idLedger,
                ]);

                // Notify Owner via CetakiaBot personal chat
                self::notifyOwnerChatbot($payload);

                return $delivery;
            }

            // 2. Client error 400: FAILED_VALIDATION (DO NOT AUTO RETRY)
            if ($httpStatus === 400) {
                $delivery->delivery_status = TransferDelivery::STATUS_FAILED_VALIDATION;
                $delivery->last_error_code = '400_VALIDATION_ERROR';
                $delivery->last_error_message = $responseJson['message'] ?? $response->body();
                $delivery->next_retry_at = null;
                $delivery->save();

                Log::warning("SyirkahTransferService: Transfer request {$idPengajuan} failed with 400 validation error. Auto-retry stopped.", [
                    'response' => $delivery->response_body,
                ]);

                return $delivery;
            }

            // 3. Client error 401: FAILED_AUTH (DO NOT AUTO RETRY)
            if ($httpStatus === 401) {
                $delivery->delivery_status = TransferDelivery::STATUS_FAILED_AUTH;
                $delivery->last_error_code = '401_UNAUTHORIZED';
                $delivery->last_error_message = $responseJson['message'] ?? 'Authentication failed (Invalid X-Syirkah-Kunci secret).';
                $delivery->next_retry_at = null;
                $delivery->save();

                Log::error("SyirkahTransferService: Transfer request {$idPengajuan} failed with 401 authentication error. Check API_KEY. Auto-retry stopped.");

                return $delivery;
            }

            // 4. Server error (404, 5xx): AUTO RETRY
            self::markForRetry($delivery, $httpStatus, $responseJson['message'] ?? "HTTP {$httpStatus}");

            return $delivery;

        } catch (\Throwable $e) {
            // Network failure / Timeout / Connection Exception: AUTO RETRY
            Log::warning("SyirkahTransferService: Network exception for {$idPengajuan}: " . $e->getMessage());

            $delivery->last_http_status = 0;
            $delivery->response_body = ['exception' => $e->getMessage()];
            self::markForRetry($delivery, 0, $e->getMessage());

            return $delivery;
        }
    }

    /**
     * Mark a delivery for retry based on the backoff schedule (Section 16).
     * Sequence: 1 min -> 5 min -> 15 min -> 30 min (Max 24 hours).
     */
    protected static function markForRetry(TransferDelivery $delivery, int $httpStatus, string $errorMessage): void
    {
        $delivery->retry_count = ($delivery->retry_count ?? 0) + 1;
        $delivery->first_failure_at = $delivery->first_failure_at ?: now();
        $delivery->last_error_code = $httpStatus ? "HTTP_{$httpStatus}" : 'NETWORK_ERROR';
        $delivery->last_error_message = $errorMessage;

        // Check if 24 hours retry window has expired
        if ($delivery->first_failure_at->diffInHours(now()) >= 24) {
            $delivery->delivery_status = TransferDelivery::STATUS_FAILED_SEND;
            $delivery->next_retry_at = null;
            $delivery->save();

            Log::error("SyirkahTransferService: Max 24-hour retry window exhausted for {$delivery->id_pengajuan}. Marked as FAILED_SEND.");
            return;
        }

        // Calculate backoff: #1 -> 1m, #2 -> 5m, #3 -> 15m, #4+ -> 30m
        $delayMinutes = match ($delivery->retry_count) {
            1 => 1,
            2 => 5,
            3 => 15,
            default => 30,
        };

        $delivery->delivery_status = TransferDelivery::STATUS_RETRYING;
        $delivery->next_retry_at = now()->addMinutes($delayMinutes);
        $delivery->save();

        Log::info("SyirkahTransferService: Request {$delivery->id_pengajuan} scheduled for retry #{$delivery->retry_count} in {$delayMinutes} minute(s) at {$delivery->next_retry_at->toIso8601String()}.");
    }

    /**
     * Send brief confirmation message to Owner's personal chat via CetakiaBot.
     * Required format:
     * "Pengajuan Syirkah {{nama}} - {{akun bank}} {{nama bank}} dengan nominal {{amount nominal}} siap dibayar"
     */
    public static function notifyOwnerChatbot(array $payload): void
    {
        $nama = $payload['nama'] ?? '-';
        $norek = $payload['norek'] ?? '-';
        $bank = $payload['bank'] ?? '-';
        $nominalFormatted = 'Rp ' . number_format($payload['nominal'] ?? 0, 0, ',', '.');

        $message = "Pengajuan Syirkah {$nama} - {$norek} {$bank} dengan nominal {$nominalFormatted} siap dibayar";

        // Resolve Owner & Superadmin private chat IDs
        $ownerChatIds = [];

        // 1. Always include explicitly configured TELEGRAM_OWNER_CHAT_ID from config / .env
        $envChatId = config('services.telegram.owner_chat_id', env('TELEGRAM_OWNER_CHAT_ID'));
        if (!empty($envChatId) && !str_starts_with(trim($envChatId), '-')) {
            $ownerChatIds[] = trim($envChatId);
        }

        // 2. Include all users with role owner or superadmin who have a valid numeric chat_code
        $ownerUsers = User::whereIn('group', ['owner', 'superadmin'])
            ->whereNotNull('chat_code')
            ->where('chat_code', '!=', '')
            ->get();

        foreach ($ownerUsers as $ou) {
            if (!empty($ou->chat_code) && !str_starts_with(trim($ou->chat_code), '-')) {
                $ownerChatIds[] = trim($ou->chat_code);
            }
        }

        $ownerChatIds = array_unique(array_filter($ownerChatIds));
        if (!empty($ownerChatIds)) {
            TelegramNotificationService::sendMessage($ownerChatIds, $message);
        }
    }

    /**
     * Check entire approved payment queue and process transfer requests sequentially.
     * "cek seluruh antrean pembayaran dan kirimkan antrean pembayaran secara berurutan ke SYIRKAH_TRANSFER_URL"
     */
    public static function processPaymentQueue(bool $isTest = false, ?int $limit = 50): array
    {
        // Find approved withdrawals waiting for payment that haven't been successfully sent yet
        $alreadySentIds = TransferDelivery::where('delivery_status', TransferDelivery::STATUS_SENT)
            ->pluck('application_id')
            ->toArray();

        $query = SavingWithdrawal::with(['user.paymentMethod', 'ownerApprover'])
            ->where('status', 'approved')
            ->whereNull('paid_at')
            ->whereNotNull('owner_approved_by')
            ->whereNotIn('id', $alreadySentIds)
            ->whereHas('ownerApprover', function ($q) {
                $q->whereIn('group', ['owner', 'superadmin']);
            })
            ->orderBy('owner_approved_at', 'asc')
            ->orderBy('id', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        $queue = $query->get();

        $results = [
            'total_found' => $queue->count(),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($queue as $withdrawal) {
            $delivery = self::send($withdrawal, $isTest);

            $results['processed']++;
            if ($delivery->isSuccessful()) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'id_pengajuan' => $delivery->id_pengajuan,
                'application_id' => $withdrawal->id,
                'status' => $delivery->delivery_status,
                'http_status' => $delivery->last_http_status,
                'error' => $delivery->last_error_message,
            ];

            // Small delay to prevent network congestion
            usleep(200000); // 0.2s
        }

        return $results;
    }

    /**
     * Retry all deliveries currently marked as RETRYING where next_retry_at is due.
     */
    public static function retryPendingDeliveries(?int $limit = 50): array
    {
        $deliveries = TransferDelivery::pendingRetry()
            ->with(['application.user.paymentMethod', 'application.ownerApprover'])
            ->limit($limit)
            ->get();

        $results = [
            'total_due' => $deliveries->count(),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($deliveries as $delivery) {
            if (!$delivery->application) {
                continue;
            }

            $isTest = str_starts_with($delivery->id_pengajuan, 'UJI-');
            $updatedDelivery = self::send($delivery->application, $isTest);

            $results['processed']++;
            if ($updatedDelivery->isSuccessful()) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'id_pengajuan' => $updatedDelivery->id_pengajuan,
                'status' => $updatedDelivery->delivery_status,
                'retry_count' => $updatedDelivery->retry_count,
            ];

            usleep(200000);
        }

        return $results;
    }
}

