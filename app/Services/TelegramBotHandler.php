<?php

namespace App\Services;

use App\Models\SavingSummary;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramBotHandler
{
    /**
     * Process an incoming update from Telegram (Webhook or Polling).
     */
    public static function handleUpdate(array $update): void
    {
        // 1. Handle Callback Query (Button Clicks)
        if (isset($update['callback_query'])) {
            self::handleCallbackQuery($update['callback_query']);
            return;
        }

        // 2. Handle Text Messages / Commands
        if (isset($update['message'])) {
            self::handleMessage($update['message']);
            return;
        }
    }

    /**
     * Handle incoming text messages and commands.
     */
    protected static function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        if (!$chatId) return;

        $fromId = $message['from']['id'] ?? $chatId;
        $username = $message['from']['username'] ?? null;
        $firstName = $message['from']['first_name'] ?? 'User';
        $text = trim($message['text'] ?? '');

        // Find user by Chat ID or Telegram Username
        $user = User::where('chat_code', (string) $fromId)
            ->orWhere(function ($q) use ($username) {
                if ($username) {
                    $q->where('telegram', '@' . $username)
                      ->orWhere('telegram', $username);
                }
            })
            ->first();

        // Auto-bind / sync numeric chat_code in database if user matched by username
        if ($user && (empty($user->chat_code) || $user->chat_code !== (string) $fromId)) {
            $user->update(['chat_code' => (string) $fromId]);
        }

        // =========================================================================
        // 1. CHECK CONVERSATION STATE / MULTI-STEP FLOW
        // =========================================================================
        $cacheKey = "tg_action_{$fromId}";
        if (Cache::has($cacheKey)) {
            $state = Cache::pull($cacheKey);

            // If user typed cancellation
            if (in_array(strtolower($text), ['/batal', '/cancel', 'batal', 'cancel'])) {
                TelegramNotificationService::sendMessage(
                    $chatId,
                    "❌ <i>Aksi persetujuan/penolakan telah dibatalkan. Konteks percakapan telah dibersihkan.</i>"
                );
                return;
            }

            $action = $state['action'] ?? null;
            $withdrawalId = $state['withdrawal_id'] ?? null;
            $origMessageId = $state['message_id'] ?? null;

            $withdrawal = SavingWithdrawal::with(['user.division', 'user.paymentMethod', 'masterSaving'])->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::sendMessage(
                    $chatId,
                    "⚠️ <b>Pengajuan Tidak Ditemukan</b>\nData pengajuan penarikan syirkah tidak ditemukan atau sudah dihapus."
                );
                return;
            }

            // Verify Permission
            if (!$user || $user->isSuperadmin) {
                TelegramNotificationService::sendMessage(
                    $chatId,
                    "⛔ <b>Akses Ditolak</b>\nAkun Anda tidak memiliki wewenang untuk menyetujui / menolak pengajuan syirkah."
                );
                return;
            }

            if ($user->group === 'admin' && !$user->isOwner && !$user->isSyirkah && !$user->isPayroll) {
                if ($withdrawal->user?->division_id !== $user->division_id) {
                    TelegramNotificationService::sendMessage(
                        $chatId,
                        "⛔ <b>Akses Ditolak</b>\nAnda hanya berwenang memproses pengajuan karyawan di divisi Anda."
                    );
                    return;
                }
            }

            $empName = htmlspecialchars($withdrawal->user?->name ?? '-');
            $empDiv = htmlspecialchars($withdrawal->user?->division?->name ?? '-');
            $nominalFormatted = number_format($withdrawal->total_amount, 0, ',', '.');
            $dateNow = now()->translatedFormat('d M Y, H:i') . ' WIB';

            // --- 1.A ACTION: ADMIN ACCEPT (Manager Division) ---
            if ($action === 'accept') {
                if ($withdrawal->status === 'accepted' || $withdrawal->status === 'approved' || $withdrawal->status === 'paid') {
                    TelegramNotificationService::sendMessage(
                        $chatId,
                        "⚠️ <b>Pengajuan Sudah Disetujui</b>\nPengajuan atas nama <b>{$empName}</b> sudah disetujui sebelumnya."
                    );
                    return;
                }

                if ($withdrawal->status === 'rejected') {
                    TelegramNotificationService::sendMessage(
                        $chatId,
                        "⚠️ <b>Pengajuan Sudah Ditolak</b>\nPengajuan atas nama <b>{$empName}</b> sudah ditolak sebelumnya."
                    );
                    return;
                }

                $note = (in_array(strtolower($text), ['-', 'ok', 'acc', 'setuju', 'disetujui']))
                    ? 'Disetujui via Telegram oleh ' . ($user->name ?? $firstName)
                    : $text;

                try {
                    SavingTransactionService::approveWithdrawalRequest($withdrawalId, $user->id, $note);

                    $reply = "✅ <b>Terima kasih! Rekomendasi Persetujuan Berhasil Dikirim.</b>\n\n";
                    $reply .= "Pengajuan penarikan syirkah atas nama <b>{$empName}</b> (Rp {$nominalFormatted}) telah <b>DISETUJUI ADMIN (ACCEPTED)</b>.\n";
                    $reply .= "📝 <b>Catatan Admin</b>: " . htmlspecialchars($note) . "\n\n";
                    $reply .= "<i>Pengajuan diteruskan ke Owner untuk approval final & penentuan nominal pencairan.</i>";

                    TelegramNotificationService::sendMessage($chatId, $reply);

                    if ($origMessageId) {
                        $updatedText = "✅ <b>PENGAJUAN DISETUJUI ADMIN DIVISI (ACCEPTED)</b>\n";
                        $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $updatedText .= "👤 <b>Karyawan</b> : {$empName} ({$empDiv})\n";
                        $updatedText .= "💰 <b>Nominal Diajukan</b> : <b>Rp {$nominalFormatted}</b>\n";
                        $updatedText .= "✍️ <b>Disetujui Admin</b> : " . htmlspecialchars($user->name ?? $firstName) . " (@" . htmlspecialchars($username ?? 'admin') . ")\n";
                        $updatedText .= "📝 <b>Catatan</b> : " . htmlspecialchars($note) . "\n";
                        $updatedText .= "📅 <b>Waktu ACC</b> : {$dateNow}\n";
                        $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $updatedText .= "Status: <b>Menunggu Approval Owner</b>";

                        TelegramNotificationService::editMessageText($chatId, $origMessageId, $updatedText);
                    }
                } catch (\Throwable $e) {
                    TelegramNotificationService::sendMessage($chatId, "❌ Gagal memproses persetujuan: " . $e->getMessage());
                }

                return;
            }

            // --- 1.B ACTION: OWNER APPROVAL STEP 1 (Input Nominal Disetujui) ---
            if ($action === 'owner_approve_step1_nominal') {
                $rawInput = trim($text);
                $isFull = in_array(strtolower($rawInput), ['tetap', 'full', 'penuh', 'semua', '-', 'ok', 'acc']);

                if ($isFull) {
                    $approvedAmount = (float) $withdrawal->total_amount;
                } else {
                    $cleaned = preg_replace('/[^0-9]/', '', $rawInput);
                    $approvedAmount = (float) $cleaned;
                }

                if ($approvedAmount <= 0) {
                    // Put back state
                    Cache::put($cacheKey, $state, now()->addMinutes(10));
                    TelegramNotificationService::sendMessage(
                        $chatId,
                        "⚠️ <b>Nominal Tidak Valid</b>\nSilakan masukkan nominal berupa angka lebih dari 0 (contoh: <code>200000</code>) atau ketik <code>tetap</code> jika menyetujui nominal penuh:"
                    );
                    return;
                }

                if ($approvedAmount > $withdrawal->total_amount) {
                    $approvedAmount = (float) $withdrawal->total_amount;
                }

                // Advance to Step 2: Note
                Cache::put($cacheKey, [
                    'action' => 'owner_approve_step2_note',
                    'withdrawal_id' => $withdrawalId,
                    'approved_amount' => $approvedAmount,
                    'message_id' => $origMessageId,
                ], now()->addMinutes(10));

                $approvedNominalFormatted = number_format($approvedAmount, 0, ',', '.');
                $requestedNominalFormatted = number_format($withdrawal->total_amount, 0, ',', '.');

                $prompt = "📝 <b>Approval Owner — Langkah 2 dari 2 (Catatan / Pesan Owner)</b>\n";
                $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $prompt .= "👤 <b>Karyawan</b> : {$empName} ({$empDiv})\n";
                $prompt .= "💰 <b>Nominal Disetujui</b> : <b>Rp {$approvedNominalFormatted}</b> <i>(dari pengajuan Rp {$requestedNominalFormatted})</i>\n";
                $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $prompt .= "Silakan ketik <b>pesan / alasan approval</b> di bawah:\n";
                $prompt .= "• Contoh: <i>Disetujui 200rb sesuai kuota kas divisi</i>\n";
                $prompt .= "• Atau ketik <code>-</code> / <code>ok</code> jika tanpa catatan khusus.\n\n";
                $prompt .= "<i>Ketik /batal untuk membatalkan.</i>";

                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Langsung Simpan (Tanpa Catatan)', 'callback_data' => 'owner_direct_note_' . $withdrawalId],
                            ['text' => '❌ Batal', 'callback_data' => 'cancel_action'],
                        ],
                    ],
                ];

                TelegramNotificationService::sendMessage($chatId, $prompt, $keyboard);
                return;
            }

            // --- 1.C ACTION: OWNER APPROVAL STEP 2 (Input Catatan Owner) ---
            if ($action === 'owner_approve_step2_note') {
                $approvedAmount = (float) ($state['approved_amount'] ?? $withdrawal->total_amount);
                $note = (in_array(strtolower($text), ['-', 'ok', 'acc', 'setuju', 'disetujui', '']))
                    ? 'Disetujui oleh Owner via Telegram'
                    : $text;

                try {
                    SavingTransactionService::approveByOwnerWithdrawalRequest($withdrawalId, $user->id, $approvedAmount, $note);

                    $approvedNomFormatted = number_format($approvedAmount, 0, ',', '.');
                    $reqNomFormatted = number_format($withdrawal->total_amount, 0, ',', '.');

                    $pm = $withdrawal->user?->paymentMethod;
                    $bankName = $pm?->payment_name ?? 'Belum Diatur';
                    $bankAcc = $pm?->bank_account ?? '-';
                    $accName = $pm?->account_name ?? ($withdrawal->user?->name ?? '-');

                    $reply = "✅ <b>Persetujuan Owner Berhasil Disimpan!</b>\n";
                    $reply .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $reply .= "👤 <b>Karyawan</b> : <b>{$empName}</b> ({$empDiv})\n";
                    $reply .= "💰 <b>Nominal Disetujui</b> : <b>Rp {$approvedNomFormatted}</b> <i>(Pengajuan: Rp {$reqNomFormatted})</i>\n";
                    $reply .= "📝 <b>Catatan Owner</b> : <i>\"" . htmlspecialchars($note) . "\"</i>\n\n";
                    $reply .= "💳 <b>REKENING PEMBAYARAN KARYAWAN:</b>\n";
                    $reply .= "   ├─ 🏦 <b>Bank / E-Wallet</b> : <b>" . htmlspecialchars($bankName) . "</b>\n";
                    $reply .= "   ├─ 🔢 <b>No. Rekening</b>    : <code>" . htmlspecialchars($bankAcc) . "</code> <i>(Tap untuk salin)</i>\n";
                    $reply .= "   └─ 👤 <b>Atas Nama</b>       : <b>" . htmlspecialchars($accName) . "</b>\n";
                    $reply .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $reply .= "Pengajuan kini telah masuk ke <b>Antrean Pembayaran</b>.\n";
                    $reply .= "Silakan transfer dana lalu klik tombol <b>Bayar (PAID)</b> di bawah:";

                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '💵 Tandai Telah Dibayar (PAID)',
                                    'callback_data' => 'paid_wd_' . $withdrawalId,
                                ],
                            ],
                            [
                                [
                                    'text' => '📋 Buka Antrean Pembayaran',
                                    'callback_data' => 'cmd_pembayaran',
                                ],
                            ],
                        ],
                    ];

                    TelegramNotificationService::sendMessage($chatId, $reply, $keyboard);

                    if ($origMessageId) {
                        $updatedText = "✅ <b>PENGAJUAN TELAH DISETUJUI OWNER (APPROVED)</b>\n";
                        $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $updatedText .= "👤 <b>Karyawan</b> : {$empName} ({$empDiv})\n";
                        $updatedText .= "💰 <b>Nominal Disetujui</b> : <b>Rp {$approvedNomFormatted}</b>\n";
                        $updatedText .= "✍️ <b>Disetujui Oleh</b> : " . htmlspecialchars($user->name ?? $firstName) . " (@" . htmlspecialchars($username ?? 'owner') . ")\n";
                        $updatedText .= "📝 <b>Catatan</b> : " . htmlspecialchars($note) . "\n";
                        $updatedText .= "📅 <b>Waktu</b> : {$dateNow}\n";
                        $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $updatedText .= "Status: <b>Menunggu Pembayaran (PAID)</b>";

                        TelegramNotificationService::editMessageText($chatId, $origMessageId, $updatedText);
                    }
                } catch (\Throwable $e) {
                    TelegramNotificationService::sendMessage($chatId, "❌ Gagal memproses persetujuan owner: " . $e->getMessage());
                }

                return;
            }

            // --- 1.D ACTION: REJECT (Admin or Owner) ---
            if ($action === 'reject') {
                if ($withdrawal->status === 'paid') {
                    TelegramNotificationService::sendMessage(
                        $chatId,
                        "⚠️ <b>Pengajuan Sudah Dibayarkan</b>\nPengajuan atas nama <b>{$empName}</b> sudah selesai dibayarkan (PAID) dan tidak dapat ditolak."
                    );
                    return;
                }

                if ($withdrawal->status === 'rejected') {
                    TelegramNotificationService::sendMessage(
                        $chatId,
                        "⚠️ <b>Pengajuan Sudah Ditolak</b>\nPengajuan atas nama <b>{$empName}</b> sudah ditolak sebelumnya."
                    );
                    return;
                }

                $reason = (empty($text) || in_array(strtolower($text), ['-', 'tolak']))
                    ? 'Ditolak via Telegram oleh ' . ($user->name ?? $firstName)
                    : $text;

                try {
                    SavingTransactionService::rejectWithdrawalRequest($withdrawalId, $user->id, $reason);

                    $reply = "✅ <b>Terima kasih! Permintaan telah berhasil diproses.</b>\n\n";
                    $reply .= "Pengajuan penarikan syirkah atas nama <b>{$empName}</b> (Rp {$nominalFormatted}) telah <b>DITOLAK (REJECTED)</b>.\n";
                    $reply .= "🚫 <b>Alasan</b>: <i>\"" . htmlspecialchars($reason) . "\"</i>\n\n";
                    $reply .= "<i>Status pengajuan kini berstatus REJECTED dan saldo tidak terpotong. Konteks percakapan telah ditutup.</i>";

                    TelegramNotificationService::sendMessage($chatId, $reply);

                    if ($origMessageId) {
                        $updatedText = "❌ <b>PENGAJUAN PENARIKAN TELAH DITOLAK (REJECTED)</b>\n";
                        $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $updatedText .= "👤 <b>Karyawan</b> : {$empName} ({$empDiv})\n";
                        $updatedText .= "💰 <b>Nominal</b>  : Rp {$nominalFormatted}\n";
                        $updatedText .= "🚫 <b>Ditolak Oleh</b> : " . htmlspecialchars($user->name ?? $firstName) . " (@" . htmlspecialchars($username ?? 'admin') . ")\n";
                        $updatedText .= "📝 <b>Alasan</b> : " . htmlspecialchars($reason) . "\n";
                        $updatedText .= "📅 <b>Waktu</b> : {$dateNow}\n";
                        $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $updatedText .= "Status: <b>DITOLAK (Saldo tidak terpotong)</b>";

                        TelegramNotificationService::editMessageText($chatId, $origMessageId, $updatedText);
                    }
                } catch (\Throwable $e) {
                    TelegramNotificationService::sendMessage($chatId, "❌ Gagal memproses penolakan: " . $e->getMessage());
                }

                return;
            }
        }

        // =========================================================================
        // 2. STANDARD BOT COMMANDS
        // =========================================================================
        $command = strtolower(explode(' ', $text)[0] ?? '');

        switch ($command) {
            case '/start':
            case '/menu':
            case '/help':
            case '/bantuan':
                self::sendWelcomeMessage($chatId, $fromId, $username, $firstName, $user);
                break;

            case '/pembayaran':
            case '/bayar':
            case '/payment':
                self::sendPaymentQueueList($chatId, $user);
                break;

            case '/saldo':
            case '/balance':
                self::sendBalanceMessage($chatId, $user);
                break;

            case '/pengajuan':
            case '/pending':
            case '/antrean':
                self::sendPendingWithdrawalsList($chatId, $user);
                break;

            case '/id':
            case '/chatid':
            case '/myid':
                self::sendChatIdMessage($chatId, $fromId, $username);
                break;

            case '/batal':
            case '/cancel':
                Cache::forget("tg_action_{$fromId}");
                TelegramNotificationService::sendMessage($chatId, "ℹ️ Tidak ada aksi pending yang aktif.");
                break;

            default:
                // Friendly default reply with menu
                self::sendWelcomeMessage($chatId, $fromId, $username, $firstName, $user);
                break;
        }
    }

    /**
     * Handle button click callbacks.
     */
    protected static function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'] ?? '';
        $data = $callbackQuery['data'] ?? '';
        $fromId = $callbackQuery['from']['id'] ?? null;
        $fromUsername = $callbackQuery['from']['username'] ?? null;
        $fromFirstName = $callbackQuery['from']['first_name'] ?? 'Admin';
        $chatId = $callbackQuery['message']['chat']['id'] ?? $fromId;
        $messageId = $callbackQuery['message']['message_id'] ?? null;

        // Find approving user
        $approverUser = User::where('chat_code', (string) $fromId)
            ->orWhere(function ($q) use ($fromUsername) {
                if ($fromUsername) {
                    $q->where('telegram', '@' . $fromUsername)
                      ->orWhere('telegram', $fromUsername);
                }
            })
            ->first();

        // 1. Cancel Action Callback
        if ($data === 'cancel_action') {
            Cache::forget("tg_action_{$fromId}");
            TelegramNotificationService::answerCallbackQuery($callbackId, 'Aksi dibatalkan.');
            TelegramNotificationService::sendMessage(
                $chatId,
                "❌ <i>Aksi persetujuan/penolakan telah dibatalkan. Konteks percakapan telah dibersihkan.</i>"
            );
            return;
        }

        // 2. Menu Callbacks
        if ($data === 'cmd_pembayaran') {
            TelegramNotificationService::answerCallbackQuery($callbackId, 'Memuat antrean pembayaran...');
            self::sendPaymentQueueList($chatId, $approverUser);
            return;
        }

        if ($data === 'cmd_saldo') {
            TelegramNotificationService::answerCallbackQuery($callbackId, 'Memuat data saldo...');
            self::sendBalanceMessage($chatId, $approverUser);
            return;
        }

        if ($data === 'cmd_pending') {
            TelegramNotificationService::answerCallbackQuery($callbackId, 'Memuat daftar antrean pengajuan...');
            self::sendPendingWithdrawalsList($chatId, $approverUser);
            return;
        }

        if ($data === 'cmd_id') {
            TelegramNotificationService::answerCallbackQuery($callbackId);
            self::sendChatIdMessage($chatId, $fromId, $fromUsername);
            return;
        }

        // 3. Action: Initiate Owner Approval (Step 1: Set Nominal)
        if (str_starts_with($data, 'owner_acc_wd_')) {
            $withdrawalId = substr($data, 13);
            $withdrawal = SavingWithdrawal::with(['user.division', 'masterSaving'])->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan atau sudah dihapus.', true);
                return;
            }

            if ($withdrawal->status === 'approved' || $withdrawal->status === 'paid') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah disetujui owner sebelumnya.', true);
                return;
            }

            if ($withdrawal->status === 'rejected') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah ditolak sebelumnya.', true);
                return;
            }

            if ($approverUser?->isSuperadmin) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Role Superadmin tidak memiliki akses ke Syirkah.', true);
                return;
            }

            // Save state to Cache (10 minutes expiry)
            Cache::put("tg_action_{$fromId}", [
                'action' => 'owner_approve_step1_nominal',
                'withdrawal_id' => $withdrawalId,
                'message_id' => $messageId,
            ], now()->addMinutes(10));

            TelegramNotificationService::answerCallbackQuery($callbackId, 'Silakan masukkan nominal yang disetujui.');

            $empName = htmlspecialchars($withdrawal->user?->name ?? '-');
            $empDiv = htmlspecialchars($withdrawal->user?->division?->name ?? '-');
            $nom = number_format($withdrawal->total_amount, 0, ',', '.');
            $type = htmlspecialchars($withdrawal->withdrawal_type_label);

            $prompt = "✍️ <b>Approval Owner — Langkah 1 dari 2 (Atur Nominal Disetujui)</b>\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "👤 <b>Karyawan</b> : {$empName} ({$empDiv})\n";
            $prompt .= "💰 <b>Pengajuan Awal</b> : <b>Rp {$nom}</b>\n";
            $prompt .= "📑 <b>Opsi</b> : {$type}\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "Silakan ketik <b>nominal yang disetujui</b>:\n";
            $prompt .= "• Ketik angka nominal (contoh: <code>200000</code> atau <code>200.000</code>)\n";
            $prompt .= "• Atau klik tombol di bawah untuk menyetujui nominal penuh (Rp {$nom}):\n\n";
            $prompt .= "<i>Ketik /batal untuk membatalkan.</i>";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => "✅ Setujui Penuh (Rp {$nom})", 'callback_data' => 'owner_full_nom_' . $withdrawalId],
                    ],
                    [
                        ['text' => '❌ Batal', 'callback_data' => 'cancel_action'],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $prompt, $keyboard);
            return;
        }

        // 4. Action: Owner Quick Full Nominal Approval
        if (str_starts_with($data, 'owner_full_nom_')) {
            $withdrawalId = substr($data, 15);
            $withdrawal = SavingWithdrawal::with(['user.division', 'masterSaving'])->find($withdrawalId);

            if (!$withdrawal || ($withdrawal->status !== 'pending' && $withdrawal->status !== 'accepted')) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan atau sudah diproses.', true);
                return;
            }

            Cache::put("tg_action_{$fromId}", [
                'action' => 'owner_approve_step2_note',
                'withdrawal_id' => $withdrawalId,
                'approved_amount' => (float) $withdrawal->total_amount,
                'message_id' => $messageId,
            ], now()->addMinutes(10));

            TelegramNotificationService::answerCallbackQuery($callbackId, 'Nominal penuh disetujui. Silakan masukkan catatan.');

            $empName = htmlspecialchars($withdrawal->user?->name ?? '-');
            $nom = number_format($withdrawal->total_amount, 0, ',', '.');

            $prompt = "📝 <b>Approval Owner — Langkah 2 dari 2 (Catatan / Pesan Owner)</b>\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "👤 <b>Karyawan</b> : {$empName}\n";
            $prompt .= "💰 <b>Nominal Disetujui</b> : <b>Rp {$nom} (Penuh)</b>\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "Silakan ketik <b>pesan / alasan approval</b> di bawah:\n";
            $prompt .= "• Atau klik tombol <i>Langsung Simpan</i> jika tanpa catatan khusus:\n\n";
            $prompt .= "<i>Ketik /batal untuk membatalkan.</i>";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Langsung Simpan (Tanpa Catatan)', 'callback_data' => 'owner_direct_note_' . $withdrawalId],
                        ['text' => '❌ Batal', 'callback_data' => 'cancel_action'],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $prompt, $keyboard);
            return;
        }

        // 5. Action: Owner Direct Save Note (Default Note)
        if (str_starts_with($data, 'owner_direct_note_')) {
            $withdrawalId = substr($data, 18);
            $withdrawal = SavingWithdrawal::with(['user.division', 'user.paymentMethod', 'masterSaving'])->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan.', true);
                return;
            }

            $cacheData = Cache::pull("tg_action_{$fromId}");
            $approvedAmount = (float) ($cacheData['approved_amount'] ?? $withdrawal->total_amount);
            $ownerId = $approverUser?->id ?? User::where('group', 'owner')->value('id');

            try {
                SavingTransactionService::approveByOwnerWithdrawalRequest(
                    $withdrawalId,
                    $ownerId,
                    $approvedAmount,
                    'Disetujui penuh oleh Owner via Telegram'
                );

                TelegramNotificationService::answerCallbackQuery($callbackId, '✅ Berhasil disetujui Owner!', true);

                $approvedNomFormatted = number_format($approvedAmount, 0, ',', '.');
                $reqNomFormatted = number_format($withdrawal->total_amount, 0, ',', '.');
                $empName = htmlspecialchars($withdrawal->user?->name ?? '-');
                $pm = $withdrawal->user?->paymentMethod;
                $bankName = $pm?->payment_name ?? 'Belum Diatur';
                $bankAcc = $pm?->bank_account ?? '-';
                $accName = $pm?->account_name ?? ($withdrawal->user?->name ?? '-');

                $reply = "✅ <b>Persetujuan Owner Berhasil Disimpan!</b>\n";
                $reply .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $reply .= "👤 <b>Karyawan</b> : <b>{$empName}</b>\n";
                $reply .= "💰 <b>Nominal Disetujui</b> : <b>Rp {$approvedNomFormatted}</b> <i>(Pengajuan: Rp {$reqNomFormatted})</i>\n\n";
                $reply .= "💳 <b>REKENING PEMBAYARAN KARYAWAN:</b>\n";
                $reply .= "   ├─ 🏦 <b>Bank / E-Wallet</b> : <b>" . htmlspecialchars($bankName) . "</b>\n";
                $reply .= "   ├─ 🔢 <b>No. Rekening</b>    : <code>" . htmlspecialchars($bankAcc) . "</code> <i>(Tap untuk salin)</i>\n";
                $reply .= "   └─ 👤 <b>Atas Nama</b>       : <b>" . htmlspecialchars($accName) . "</b>\n";
                $reply .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $reply .= "Pengajuan telah masuk ke <b>Antrean Pembayaran</b>. Silakan lakukan transfer:";

                $keyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '💵 Tandai Telah Dibayar (PAID)',
                                'callback_data' => 'paid_wd_' . $withdrawalId,
                            ],
                        ],
                        [
                            [
                                'text' => '📋 Buka Antrean Pembayaran',
                                'callback_data' => 'cmd_pembayaran',
                            ],
                        ],
                    ],
                ];

                TelegramNotificationService::sendMessage($chatId, $reply, $keyboard);
            } catch (\Throwable $e) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Gagal: ' . $e->getMessage(), true);
            }
            return;
        }

        // 6. Action: Admin Division Direct Approve
        if (str_starts_with($data, 'acc_wd_')) {
            $withdrawalId = substr($data, 7);
            $withdrawal = SavingWithdrawal::with(['user.division', 'masterSaving'])->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan.', true);
                return;
            }

            if ($withdrawal->status === 'accepted' || $withdrawal->status === 'approved' || $withdrawal->status === 'paid') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah disetujui sebelumnya.', true);
                return;
            }

            if ($withdrawal->status === 'rejected') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah ditolak sebelumnya.', true);
                return;
            }

            if ($approverUser?->isSuperadmin) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Role Superadmin tidak memiliki akses ke Syirkah.', true);
                return;
            }

            if ($approverUser && $approverUser->group === 'admin' && !$approverUser->isOwner && !$approverUser->isSyirkah && !$approverUser->isPayroll) {
                if ($withdrawal->user?->division_id !== $approverUser->division_id) {
                    TelegramNotificationService::answerCallbackQuery($callbackId, 'Anda hanya berwenang memproses pengajuan divisi Anda.', true);
                    return;
                }
            }

            Cache::put("tg_action_{$fromId}", [
                'action' => 'accept',
                'withdrawal_id' => $withdrawalId,
                'message_id' => $messageId,
            ], now()->addMinutes(10));

            TelegramNotificationService::answerCallbackQuery($callbackId, 'Silakan ketik catatan approval di chat.');

            $empName = htmlspecialchars($withdrawal->user?->name ?? '-');
            $empDiv = htmlspecialchars($withdrawal->user?->division?->name ?? '-');
            $nom = number_format($withdrawal->total_amount, 0, ',', '.');
            $type = htmlspecialchars($withdrawal->withdrawal_type_label);

            $prompt = "✍️ <b>Konfirmasi Persetujuan Admin Divisi</b>\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "👤 <b>Karyawan</b> : {$empName} ({$empDiv})\n";
            $prompt .= "💰 <b>Nominal</b>  : <b>Rp {$nom}</b>\n";
            $prompt .= "📑 <b>Opsi</b>     : {$type}\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "Silakan ketikan alasan / catatan <b>ACCEPTED</b> di bawah:\n\n";
            $prompt .= "<i>Ketik /batal atau klik tombol di bawah untuk membatalkan.</i>";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Setujui Langsung (Tanpa Catatan)', 'callback_data' => 'acc_direct_' . $withdrawalId],
                        ['text' => '❌ Batal', 'callback_data' => 'cancel_action'],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $prompt, $keyboard);
            return;
        }

        // 7. Action: Direct Approve Withdrawal (Bypass Typing Note for Admin)
        if (str_starts_with($data, 'acc_direct_')) {
            $withdrawalId = substr($data, 11);
            Cache::forget("tg_action_{$fromId}");

            $withdrawal = SavingWithdrawal::with(['user.division', 'masterSaving'])->find($withdrawalId);
            if (!$withdrawal || $withdrawal->status !== 'pending') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan atau sudah diproses.', true);
                return;
            }

            try {
                $approverId = $approverUser?->id ?? User::where('group', 'owner')->value('id');
                SavingTransactionService::approveWithdrawalRequest($withdrawalId, $approverId);

                TelegramNotificationService::answerCallbackQuery($callbackId, '✅ Berhasil disetujui Admin!', true);

                $empName = htmlspecialchars($withdrawal->user?->name ?? '-');
                $nominalFormatted = number_format($withdrawal->total_amount, 0, ',', '.');

                $reply = "✅ <b>Terima kasih! Rekomendasi Persetujuan Berhasil Dikirim.</b>\n\n";
                $reply .= "Pengajuan penarikan syirkah atas nama <b>{$empName}</b> (Rp {$nominalFormatted}) telah <b>DISETUJUI ADMIN (ACCEPTED)</b>.\n\n";
                $reply .= "<i>Pengajuan diteruskan ke Owner untuk approval final.</i>";

                TelegramNotificationService::sendMessage($chatId, $reply);

                if ($messageId) {
                    $dateNow = now()->translatedFormat('d M Y, H:i') . ' WIB';
                    $updatedText = "✅ <b>PENGAJUAN DISETUJUI ADMIN DIVISI (ACCEPTED)</b>\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "👤 <b>Karyawan</b> : " . htmlspecialchars($withdrawal->user?->name ?? '-') . "\n";
                    $updatedText .= "💰 <b>Nominal Diajukan</b> : <b>Rp " . number_format($withdrawal->total_amount, 0, ',', '.') . "</b>\n";
                    $updatedText .= "✍️ <b>Disetujui Oleh</b> : " . htmlspecialchars($fromFirstName) . " (@" . htmlspecialchars($fromUsername ?? 'admin') . ")\n";
                    $updatedText .= "📅 <b>Waktu ACC</b> : {$dateNow}\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "Status: <b>Menunggu Approval Owner</b>";

                    TelegramNotificationService::editMessageText($chatId, $messageId, $updatedText);
                }
            } catch (\Throwable $e) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Gagal menyetujui: ' . $e->getMessage(), true);
            }
            return;
        }

        // 8. Action: Initiate Reject Withdrawal
        if (str_starts_with($data, 'rej_wd_')) {
            $withdrawalId = substr($data, 7);
            $withdrawal = SavingWithdrawal::with(['user.division', 'masterSaving'])->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan.', true);
                return;
            }

            if ($withdrawal->status === 'paid') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah dibayarkan (PAID), tidak dapat ditolak.', true);
                return;
            }

            if ($withdrawal->status === 'rejected') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah ditolak sebelumnya.', true);
                return;
            }

            if ($approverUser?->isSuperadmin) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Role Superadmin tidak memiliki akses ke Syirkah.', true);
                return;
            }

            if ($approverUser && $approverUser->group === 'admin' && !$approverUser->isOwner && !$approverUser->isSyirkah && !$approverUser->isPayroll) {
                if ($withdrawal->user?->division_id !== $approverUser->division_id) {
                    TelegramNotificationService::answerCallbackQuery($callbackId, 'Anda hanya berwenang memproses pengajuan divisi Anda.', true);
                    return;
                }
            }

            Cache::put("tg_action_{$fromId}", [
                'action' => 'reject',
                'withdrawal_id' => $withdrawalId,
                'message_id' => $messageId,
            ], now()->addMinutes(10));

            TelegramNotificationService::answerCallbackQuery($callbackId, 'Silakan ketik alasan penolakan di chat.');

            $empName = htmlspecialchars($withdrawal->user?->name ?? '-');
            $empDiv = htmlspecialchars($withdrawal->user?->division?->name ?? '-');
            $nom = number_format($withdrawal->effective_total_amount, 0, ',', '.');
            $type = htmlspecialchars($withdrawal->withdrawal_type_label);

            $prompt = "🚫 <b>Konfirmasi Penolakan Pengajuan</b>\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "👤 <b>Karyawan</b> : {$empName} ({$empDiv})\n";
            $prompt .= "💰 <b>Nominal</b>  : <b>Rp {$nom}</b>\n";
            $prompt .= "📑 <b>Opsi</b>     : {$type}\n";
            $prompt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $prompt .= "Silakan ketikan alasan <b>REJECTED</b> di bawah:\n\n";
            $prompt .= "<i>Ketik /batal atau klik tombol di bawah untuk membatalkan.</i>";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '❌ Batal', 'callback_data' => 'cancel_action'],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $prompt, $keyboard);
            return;
        }

        // 9. Action: Mark as Paid
        if (str_starts_with($data, 'paid_wd_')) {
            $withdrawalId = substr($data, 8);
            $withdrawal = SavingWithdrawal::with(['user.division', 'user.paymentMethod'])->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan.', true);
                return;
            }

            if ($withdrawal->status === 'paid') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah ditandai PAID sebelumnya.', true);
                return;
            }

            if ($withdrawal->status === 'rejected') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah ditolak, tidak dapat dibayarkan.', true);
                return;
            }

            if ($approverUser?->isSuperadmin) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Role Superadmin tidak memiliki akses ke Syirkah.', true);
                return;
            }

            try {
                $payerId = $approverUser?->id ?? User::where('group', 'owner')->value('id');
                SavingTransactionService::markAsPaidWithdrawalRequest($withdrawalId, $payerId);

                TelegramNotificationService::answerCallbackQuery($callbackId, '💰 Berhasil ditandai telah dibayarkan (PAID)!', true);

                if ($messageId) {
                    $dateNow = now()->translatedFormat('d M Y, H:i') . ' WIB';
                    $updatedText = "💵 <b>PENARIKAN SELESAI DIBAYARKAN (PAID)</b>\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "👤 <b>Karyawan</b> : " . htmlspecialchars($withdrawal->user?->name ?? '-') . "\n";
                    $updatedText .= "💰 <b>Nominal Cair</b> : <b>Rp " . number_format($withdrawal->effective_total_amount, 0, ',', '.') . "</b>\n";
                    $updatedText .= "🏦 <b>Dibayarkan Oleh</b> : " . htmlspecialchars($fromFirstName) . " (@" . htmlspecialchars($fromUsername ?? 'finance') . ")\n";
                    $updatedText .= "📅 <b>Waktu Bayar</b> : {$dateNow}\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "Status: <b>SELESAI (PAID)</b> ✨";

                    TelegramNotificationService::editMessageText($chatId, $messageId, $updatedText);
                }
            } catch (\Throwable $e) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Gagal memproses pembayaran: ' . $e->getMessage(), true);
            }
            return;
        }
    }

    /* =========================================================================
     * RESPONSE GENERATORS
     * ========================================================================= */

    protected static function sendWelcomeMessage($chatId, $fromId, ?string $username, string $firstName, ?User $user): void
    {
        $roleName = $user ? strtoupper($user->group) : 'Tamu / Belum Terdaftar';
        $userRealName = $user ? $user->name : $firstName;

        $msg = "👋 <b>Halo, " . htmlspecialchars($userRealName) . "!</b>\n\n";
        $msg .= "Selamat datang di <b>CetakiaBot</b> — Asisten Notifikasi & Persetujuan Absensi & Syirkah Cipta Grafika.\n\n";
        $msg .= "📋 <b>Status Akun Anda:</b>\n";
        $msg .= "├─ 🆔 <b>Chat ID</b> : <code>{$fromId}</code> <i>(Klik untuk salin)</i>\n";
        $msg .= "├─ 👤 <b>Username</b>: @" . htmlspecialchars($username ?? 'none') . "\n";
        $msg .= "└─ 🎖️ <b>Role</b>     : <b>{$roleName}</b>\n\n";
        $msg .= "Pilih menu interaktif di bawah atau ketik perintah langsung:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💳 Antrean Pembayaran', 'callback_data' => 'cmd_pembayaran'],
                    ['text' => '⏳ Antrean Pengajuan', 'callback_data' => 'cmd_pending'],
                ],
                [
                    ['text' => '📊 Cek Saldo Syirkah', 'callback_data' => 'cmd_saldo'],
                    ['text' => '🆔 Salin Chat ID', 'callback_data' => 'cmd_id'],
                ],
                [
                    ['text' => '🌐 Buka Web Dashboard', 'url' => config('app.url', 'http://localhost:8000') . '/payroll/saving-transactions'],
                ],
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
    }

    /**
     * Send Payment Queue List (Antrean Pembayaran - Status: APPROVED).
     */
    protected static function sendPaymentQueueList($chatId, ?User $user): void
    {
        if ($user && $user->isSuperadmin) {
            TelegramNotificationService::sendMessage(
                $chatId,
                "ℹ️ Role Superadmin tidak memiliki akses ke antrean pembayaran Syirkah."
            );
            return;
        }

        // Fetch all approved withdrawals (waiting for payment transfer)
        $approvedList = SavingWithdrawal::with(['user.division', 'user.paymentMethod', 'masterSaving', 'approver', 'ownerApprover'])
            ->where('status', 'approved')
            ->orderBy('owner_approved_at', 'desc')
            ->take(8)
            ->get();

        if ($approvedList->isEmpty()) {
            $msg = "✨ <b>TIDAK ADA ANTREAN PEMBAYARAN</b>\n\n";
            $msg .= "Saat ini tidak ada pengajuan syirkah yang menunggu pembayaran transfer (Status APPROVED). Semua pembayaran telah diselesaikan!";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '⏳ Cek Antrean Pengajuan (ACC)', 'callback_data' => 'cmd_pending'],
                        ['text' => '📊 Cek Saldo', 'callback_data' => 'cmd_saldo'],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
            return;
        }

        $count = $approvedList->count();
        $msg = "💳 <b>DAFTAR ANTREAN PEMBAYARAN SYIRKAH (SIAP TRANSFER)</b>\n";
        $msg .= "Menampilkan {$count} pengajuan yang telah disetujui Owner dan siap dibayarkan:\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $inlineButtons = [];

        foreach ($approvedList as $idx => $wd) {
            $num = $idx + 1;
            $name = $wd->user?->name ?? 'Karyawan';
            $div = $wd->user?->division?->name ?? '-';
            $effNominal = number_format($wd->effective_total_amount, 0, ',', '.');
            $reqNominal = number_format($wd->total_amount, 0, ',', '.');
            $pm = $wd->user?->paymentMethod;
            $bankName = $pm?->payment_name ?? 'Belum Diatur';
            $bankAcc = $pm?->bank_account ?? '-';
            $accName = $pm?->account_name ?? ($wd->user?->name ?? '-');

            $msg .= "<b>[{$num}] {$name}</b> ({$div})\n";
            $msg .= "   ├─ 💵 <b>Nominal Cair</b> : <b>Rp {$effNominal}</b>";
            if ($wd->approved_total_amount !== null && $wd->approved_total_amount != $wd->total_amount) {
                $msg .= " <i>(Awal: Rp {$reqNominal})</i>";
            }
            $msg .= "\n";
            $msg .= "   ├─ 🏦 <b>Bank</b> : <b>{$bankName}</b>\n";
            $msg .= "   ├─ 🔢 <b>No. Rek</b> : <code>{$bankAcc}</code> <i>(Tap untuk salin)</i>\n";
            $msg .= "   └─ 👤 <b>A.n</b> : <b>{$accName}</b>\n";
            if ($wd->owner_note) {
                $msg .= "   💬 <i>Catatan: \"{$wd->owner_note}\"</i>\n";
            }
            $msg .= "\n";

            $shortName = explode(' ', trim($name))[0];
            $inlineButtons[] = [
                [
                    'text' => "💵 Bayar [{$num}] ({$shortName})",
                    'callback_data' => 'paid_wd_' . $wd->id,
                ],
                [
                    'text' => "❌ Tolak [{$num}]",
                    'callback_data' => 'rej_wd_' . $wd->id,
                ],
            ];
        }

        $msg .= "👉 <i>Salin nomor rekening di atas untuk transfer, lalu klik tombol Bayar setelah transfer selesai.</i>";

        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');
        $inlineButtons[] = [
            [
                'text' => '🌐 Buka Menu Mutasi Web',
                'url' => $appUrl . '/payroll/saving-transactions?activeTab=withdrawals',
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, ['inline_keyboard' => $inlineButtons]);
    }

    protected static function sendBalanceMessage($chatId, ?User $user): void
    {
        if ($user && $user->isSuperadmin) {
            TelegramNotificationService::sendMessage(
                $chatId,
                "ℹ️ Role Superadmin tidak memiliki akses ke data mutasi saldo Syirkah."
            );
            return;
        }

        $scopeTitle = "PERUSAHAAN (GLOBAL)";
        $scopeSubtitle = "🏢 <b>Cakupan</b> : Seluruh Divisi (Owner Scope)";

        $txQuery = SavingTransaction::where('status', 'approved');

        if ($user) {
            $user->loadMissing('division');
            if ($user->group === 'admin' && $user->division_id) {
                $divName = $user->division?->name ?? 'Divisi Anda';
                $txQuery->whereHas('user', fn($q) => $q->where('division_id', $user->division_id));
                $scopeTitle = "DIVISI " . strtoupper($divName);
                $scopeSubtitle = "🏢 <b>Divisi</b>  : <b>" . htmlspecialchars($divName) . "</b> <i>(Khusus Divisi Anda)</i>";
            } elseif ($user->group === 'user') {
                $txQuery->where('user_id', $user->id);
                $scopeTitle = "PRIBADI";
                $scopeSubtitle = "👤 <b>Nama</b>    : <b>" . htmlspecialchars($user->name) . "</b>";
            }
        }

        $depMan = (float) (clone $txQuery)->where('transaction_type', 'deposit')->sum('mandatory_amount');
        $wdMan = (float) (clone $txQuery)->where('transaction_type', 'withdrawal')->sum('mandatory_amount');
        $totalWajib = max(0.0, $depMan - $wdMan);

        $depSec = (float) (clone $txQuery)->where('transaction_type', 'deposit')->sum('secondary_amount');
        $wdSec = (float) (clone $txQuery)->where('transaction_type', 'withdrawal')->sum('secondary_amount');
        $totalSukarela = max(0.0, $depSec - $wdSec);

        $totalAkumulasi = $totalWajib + $totalSukarela;

        $msg = "📊 <b>INFORMASI SALDO SYIRKAH — {$scopeTitle}</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "{$scopeSubtitle}\n";
        $msg .= "🔒 <b>Total Saldo Wajib</b>    : <b>Rp " . number_format($totalWajib, 0, ',', '.') . "</b>\n";
        $msg .= "✨ <b>Total Saldo SSR/Sukarela</b> : <b>Rp " . number_format($totalSukarela, 0, ',', '.') . "</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "💰 <b>Total Akumulasi Terverifikasi</b> :\n";
        $msg .= "👉 <b>Rp " . number_format($totalAkumulasi, 0, ',', '.') . "</b>\n\n";
        $msg .= "<i>Data saldo dihitung secara real-time berdasarkan hak akses (role & divisi).</i>";

        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💳 Antrean Pembayaran', 'callback_data' => 'cmd_pembayaran'],
                    ['text' => '⏳ Antrean Pengajuan', 'callback_data' => 'cmd_pending'],
                ],
                [
                    ['text' => '🌐 Buka Web Mutasi', 'url' => $appUrl . '/payroll/saving-transactions'],
                ],
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
    }

    protected static function sendPendingWithdrawalsList($chatId, ?User $user): void
    {
        if ($user && $user->isSuperadmin) {
            TelegramNotificationService::sendMessage(
                $chatId,
                "ℹ️ Role Superadmin tidak memiliki akses ke antrean pengajuan Syirkah."
            );
            return;
        }

        $isOwnerOrFinance = $user && ($user->isOwner || $user->group === 'owner' || $user->isSyirkah || $user->isPayroll);

        // =========================================================================
        // 1. OWNER / GLOBAL FLOW (NO DIVISION SCOPE - ALL DIVISIONS)
        // =========================================================================
        if ($isOwnerOrFinance || !$user) {
            // Priority 1: Accepted (Menunggu Approval & Penentuan Nominal oleh Owner)
            $acceptedList = SavingWithdrawal::with(['user.division', 'user.paymentMethod', 'masterSaving', 'approver'])
                ->where('status', 'accepted')
                ->orderBy('approved_at', 'desc')
                ->take(5)
                ->get();

            // Priority 2: Pending (Menunggu verifikasi admin divisi)
            $pendingList = SavingWithdrawal::with(['user.division', 'masterSaving'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Check if there are approved ones
            $approvedCount = SavingWithdrawal::where('status', 'approved')->count();

            if ($acceptedList->isEmpty() && $pendingList->isEmpty()) {
                $msg = "✨ <b>TIDAK ADA ANTREAN PERSETUJUAN PENGAJUAN</b>\n\n";
                if ($approvedCount > 0) {
                    $msg .= "Saat ini ada <b>{$approvedCount} pengajuan</b> di <b>Antrean Pembayaran</b> yang menunggu transfer!";
                } else {
                    $msg .= "Semua pengajuan penarikan syirkah dari seluruh divisi telah selesai diproses!";
                }

                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => "💳 Buka Antrean Pembayaran ({$approvedCount})", 'callback_data' => 'cmd_pembayaran'],
                            ['text' => '📊 Cek Saldo', 'callback_data' => 'cmd_saldo'],
                        ],
                    ],
                ];

                TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
                return;
            }

            $msg = "📋 <b>ANTREAN PENGAJUAN SYIRKAH (GLOBAL / OWNER)</b>\n";
            $msg .= "🏢 <b>Cakupan</b> : Seluruh Divisi (Tanpa Batasan Scope)\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $inlineButtons = [];

            if ($acceptedList->isNotEmpty()) {
                $msg .= "✍️ <b>MENUNGGU APPROVAL OWNER (ACCEPTED)</b>:\n";
                foreach ($acceptedList as $idx => $wd) {
                    $num = $idx + 1;
                    $name = $wd->user?->name ?? 'Karyawan';
                    $div = $wd->user?->division?->name ?? '-';
                    $nominal = number_format($wd->total_amount, 0, ',', '.');
                    $approver = $wd->approver?->name ?? 'Admin Divisi';
                    $pm = $wd->user?->paymentMethod;
                    $bankInfo = $pm ? ($pm->payment_name . ' - ' . $pm->bank_account . ' a.n ' . ($pm->account_name ?: $name)) : 'Rekening belum diset';

                    $msg .= "<b>[{$num}] {$name}</b> ({$div})\n";
                    $msg .= "   ├─ Diajukan : <b>Rp {$nominal}</b>\n";
                    $msg .= "   ├─ Disetujui Admin : {$approver}\n";
                    $msg .= "   └─ Rekening : <code>{$bankInfo}</code>\n\n";

                    $shortName = explode(' ', trim($name))[0];
                    $inlineButtons[] = [
                        [
                            'text' => "✍️ Setujui [{$num}] ({$shortName})",
                            'callback_data' => 'owner_acc_wd_' . $wd->id,
                        ],
                        [
                            'text' => "❌ Tolak [{$num}]",
                            'callback_data' => 'rej_wd_' . $wd->id,
                        ],
                    ];
                }
            }

            if ($pendingList->isNotEmpty()) {
                $msg .= "⏳ <b>MENUNGGU VERIFIKASI ADMIN DIVISI (PENDING)</b>:\n";
                $offset = $acceptedList->count();
                foreach ($pendingList as $idx => $wd) {
                    $num = $offset + $idx + 1;
                    $name = $wd->user?->name ?? 'Karyawan';
                    $div = $wd->user?->division?->name ?? '-';
                    $nominal = number_format($wd->total_amount, 0, ',', '.');
                    $date = $wd->created_at ? $wd->created_at->translatedFormat('d M, H:i') : '-';

                    $msg .= "<b>[{$num}] {$name}</b> ({$div})\n";
                    $msg .= "   ├─ Nominal : <b>Rp {$nominal}</b>\n";
                    $msg .= "   └─ Tanggal : {$date} WIB\n\n";

                    $shortName = explode(' ', trim($name))[0];
                    $inlineButtons[] = [
                        [
                            'text' => "✍️ Setujui [{$num}] ({$shortName})",
                            'callback_data' => 'owner_acc_wd_' . $wd->id,
                        ],
                        [
                            'text' => "❌ Tolak [{$num}]",
                            'callback_data' => 'rej_wd_' . $wd->id,
                        ],
                    ];
                }
            }

            if ($approvedCount > 0) {
                $inlineButtons[] = [
                    [
                        'text' => "💳 Buka Antrean Pembayaran ({$approvedCount} Siap Transfer)",
                        'callback_data' => 'cmd_pembayaran',
                    ],
                ];
            }

            $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');
            $inlineButtons[] = [
                [
                    'text' => '🌐 Buka Menu Pengajuan Web',
                    'url' => $appUrl . '/payroll/saving-transactions?activeTab=withdrawals',
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $msg, ['inline_keyboard' => $inlineButtons]);
            return;
        }

        // =========================================================================
        // 2. ADMIN DIVISI FLOW (STRICT DIVISION SCOPE - ONLY PENDING)
        // =========================================================================
        $query = SavingWithdrawal::with(['user.division', 'masterSaving'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        if ($user->division_id) {
            $query->whereHas('user', fn($q) => $q->where('division_id', $user->division_id));
        }

        $pendingList = $query->take(5)->get();

        if ($pendingList->isEmpty()) {
            $msg = "✨ <b>TIDAK ADA ANTREAN PENGAJUAN</b>\n\n";
            $msg .= "Saat ini tidak ada pengajuan penarikan syirkah berstatus <b>PENDING</b> di divisi Anda. Semua pengajuan telah diproses!";
            TelegramNotificationService::sendMessage($chatId, $msg);
            return;
        }

        $count = $pendingList->count();
        $divName = $user->division?->name ?? 'Divisi';
        $msg = "⏳ <b>DAFTAR ANTREAN PENGAJUAN SYIRKAH (PENDING)</b>\n";
        $msg .= "🏢 <b>Divisi</b> : {$divName}\n";
        $msg .= "Menampilkan {$count} pengajuan terbaru yang menunggu persetujuan Anda:\n\n";

        $inlineButtons = [];

        foreach ($pendingList as $idx => $wd) {
            $num = $idx + 1;
            $name = $wd->user?->name ?? 'Karyawan';
            $nominal = number_format($wd->total_amount, 0, ',', '.');
            $type = $wd->withdrawal_type_label;
            $date = $wd->created_at ? $wd->created_at->translatedFormat('d M, H:i') : '-';

            $msg .= "<b>{$num}. {$name}</b>\n";
            $msg .= "   ├─ Opsi : {$type}\n";
            $msg .= "   ├─ Nominal : <b>Rp {$nominal}</b>\n";
            $msg .= "   └─ Tanggal : {$date} WIB\n\n";

            $shortName = explode(' ', trim($name))[0];
            $inlineButtons[] = [
                [
                    'text' => "✅ Setujui #{$num} ({$shortName})",
                    'callback_data' => 'acc_wd_' . $wd->id,
                ],
                [
                    'text' => "❌ Tolak #{$num}",
                    'callback_data' => 'rej_wd_' . $wd->id,
                ],
            ];
        }

        $msg .= "👉 <i>Klik tombol di bawah untuk menyetujui / menolak langsung per pengajuan, atau buka Web Dashboard.</i>";

        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');
        $inlineButtons[] = [
            [
                'text' => '🌐 Buka Menu Approval Web',
                'url' => $appUrl . '/payroll/saving-transactions?activeTab=withdrawals',
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, ['inline_keyboard' => $inlineButtons]);
    }

    protected static function sendChatIdMessage($chatId, $fromId, ?string $username): void
    {
        $msg = "🆔 <b>INFORMASI TELEGRAM CHAT ID</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Chat ID Anda : <code>{$fromId}</code> <i>(Klik untuk salin)</i>\n";
        $msg .= "Username     : @" . htmlspecialchars($username ?? 'none') . "\n\n";
        $msg .= "Masukkan Chat ID <code>{$fromId}</code> di atas ke form Admin pada aplikasi Absensi & Syirkah.";

        TelegramNotificationService::sendMessage($chatId, $msg);
    }
}
