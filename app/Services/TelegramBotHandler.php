<?php

namespace App\Services;

use App\Models\SavingSummary;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
                self::sendWelcomeMessage($chatId, $fromId, $username, $firstName, $user);
                break;

            case '/help':
            case '/bantuan':
            case '/panduan':
                self::sendHelpMessage($chatId, $user, $fromId, $username);
                break;

            case '/status':
            case '/ping':
            case '/health':
            case '/check':
                self::sendServiceStatusMessage($chatId, $user, $fromId, $username);
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

        if ($data === 'cmd_help') {
            TelegramNotificationService::answerCallbackQuery($callbackId, 'Memuat panduan bantuan...');
            self::sendHelpMessage($chatId, $approverUser, $fromId, $fromUsername);
            return;
        }

        if ($data === 'cmd_status') {
            TelegramNotificationService::answerCallbackQuery($callbackId, 'Memeriksa status layanan...');
            self::sendServiceStatusMessage($chatId, $approverUser, $fromId, $fromUsername);
            return;
        }

        if ($data === 'refresh_status') {
            TelegramNotificationService::answerCallbackQuery($callbackId, '✅ Status diperbarui!');
            self::sendServiceStatusMessage($chatId, $approverUser, $fromId, $fromUsername, $messageId);
            return;
        }

        if ($data === 'cmd_menu') {
            TelegramNotificationService::answerCallbackQuery($callbackId, 'Membuka menu utama...');
            self::sendWelcomeMessage($chatId, $fromId, $fromUsername, $fromFirstName, $approverUser);
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
        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');

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
                    ['text' => '⚡ Cek Status Layanan', 'callback_data' => 'cmd_status'],
                ],
                [
                    ['text' => '📖 Panduan & Bantuan', 'callback_data' => 'cmd_help'],
                    ['text' => '🆔 Salin Chat ID', 'callback_data' => 'cmd_id'],
                ],
                [
                    ['text' => '🌐 Buka Web Dashboard', 'url' => $appUrl . '/payroll/saving-transactions'],
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
        $msg .= "Masukkan Chat ID <code>{$fromId}</code> di atas ke form Akun / Profil pada aplikasi Absensi & Syirkah.";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⚡ Cek Status Layanan', 'callback_data' => 'cmd_status'],
                    ['text' => '📖 Panduan / Help', 'callback_data' => 'cmd_help'],
                ],
                [
                    ['text' => '🏠 Menu Utama', 'callback_data' => 'cmd_menu'],
                ],
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
    }

    /**
     * Send structured, interactive Help & Guide message tailored to user role.
     */
    protected static function sendHelpMessage($chatId, ?User $user, $fromId, ?string $username): void
    {
        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');
        $isOwnerOrFinance = $user && ($user->isOwner || $user->group === 'owner' || $user->isSyirkah || $user->isPayroll);
        $isAdmin = $user && $user->group === 'admin';
        $isRegularUser = $user && $user->group === 'user';

        // 1. OWNER / FINANCE GUIDE
        if ($isOwnerOrFinance) {
            $msg = "👑 <b>PANDUAN OPERASIONAL BOT — OWNER & FINANCE</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "Bot ini dirancang untuk mempermudah persetujuan, penyesuaian nominal, dan pembayaran syirkah secara cepat & aman.\n\n";

            $msg .= "📋 <b>ALUR KERJA OWNER (3 TAHAP):</b>\n";
            $msg .= "<b>1. Approval & Penentuan Nominal</b> ✍️\n";
            $msg .= "   • Pengajuan berstatus <code>ACCEPTED</code> (diverifikasi Manajer Divisi) akan muncul di notifikasi dan menu <b>/pengajuan</b>.\n";
            $msg .= "   • Klik <b>[✍️ Setujui]</b>, masukkan nominal yang disetujui (bisa disetujui penuh atau disesuaikan lebih rendah), lalu masukkan pesan/catatan approval.\n\n";

            $msg .= "<b>2. Pembayaran Transfer (PAID)</b> 💳\n";
            $msg .= "   • Buka <b>/pembayaran</b> untuk melihat daftar pengajuan berstatus <code>APPROVED</code> yang siap transfer.\n";
            $msg .= "   • Salin nomor rekening bank karyawan secara instan (1x klik).\n";
            $msg .= "   • Setelah transfer bank selesai, klik <b>[💵 Bayar]</b> untuk menandai PAID.\n";
            $msg .= "   • <i>Saldo mutasi syirkah karyawan akan otomatis terpotong saat status PAID.</i>\n\n";

            $msg .= "<b>3. Penolakan (REJECT)</b> ❌\n";
            $msg .= "   • Anda dapat menolak pengajuan pada tahap PENDING, ACCEPTED, maupun APPROVED dengan klik <b>[❌ Tolak]</b> dan memasukkan alasan penolakan.\n\n";

            $msg .= "⚡ <b>DAFTAR PERINTAH BOT:</b>\n";
            $msg .= "├─ <code>/start</code> — Buka menu utama interaktif\n";
            $msg .= "├─ <code>/help</code> — Panduan lengkap & alur bot\n";
            $msg .= "├─ <code>/status</code> — Cek status koneksi layanan AbsensiCipta\n";
            $msg .= "├─ <code>/pengajuan</code> — Antrean pengajuan penarikan (ACCEPTED & PENDING)\n";
            $msg .= "├─ <code>/pembayaran</code> — Antrean pembayaran siap transfer (APPROVED)\n";
            $msg .= "├─ <code>/saldo</code> — Cek total saldo syirkah perusahaan (Global)\n";
            $msg .= "├─ <code>/id</code> — Salin Telegram Chat ID Anda\n";
            $msg .= "└─ <code>/batal</code> — Batalkan proses input yang sedang aktif\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '⏳ Antrean Pengajuan (ACC)', 'callback_data' => 'cmd_pending'],
                        ['text' => '💳 Antrean Pembayaran', 'callback_data' => 'cmd_pembayaran'],
                    ],
                    [
                        ['text' => '📊 Cek Saldo Global', 'callback_data' => 'cmd_saldo'],
                        ['text' => '⚡ Cek Status Layanan', 'callback_data' => 'cmd_status'],
                    ],
                    [
                        ['text' => '🏠 Menu Utama', 'callback_data' => 'cmd_menu'],
                        ['text' => '🌐 Web Dashboard Syirkah', 'url' => $appUrl . '/payroll/saving-transactions?activeTab=withdrawals'],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
            return;
        }

        // 2. ADMIN / MANAJER DIVISI GUIDE
        if ($isAdmin) {
            $user->loadMissing('division');
            $divName = $user->division?->name ?? 'Divisi Anda';

            $msg = "👔 <b>PANDUAN OPERASIONAL BOT — MANAJER DIVISI</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "🏢 <b>Divisi Anda</b> : <b>" . htmlspecialchars($divName) . "</b>\n\n";

            $msg .= "📋 <b>ALUR KERJA MANAJER DIVISI:</b>\n";
            $msg .= "<b>1. Verifikasi Pengajuan Karyawan (PENDING)</b> 🔍\n";
            $msg .= "   • Setiap ada anggota divisi Anda yang mengajukan penarikan syirkah, notifikasi masuk ke bot Anda.\n";
            $msg .= "   • Klik <b>[✅ Setujui]</b> untuk menyetujui rekomendasi (status berubah menjadi <code>ACCEPTED</code> dan diteruskan ke Owner).\n";
            $msg .= "   • Klik <b>[❌ Tolak]</b> dan masukkan alasan penolakan jika pengajuan tidak memenuhi kriteria.\n\n";

            $msg .= "<b>2. Monitoring Saldo Divisi</b> 📊\n";
            $msg .= "   • Gunakan perintah <b>/saldo</b> untuk melihat total akumulasi saldo syirkah (Wajib & SSR) khusus anggota divisi Anda.\n\n";

            $msg .= "⚡ <b>DAFTAR PERINTAH BOT:</b>\n";
            $msg .= "├─ <code>/start</code> — Buka menu utama interaktif\n";
            $msg .= "├─ <code>/help</code> — Panduan & bantuan penggunaan bot\n";
            $msg .= "├─ <code>/status</code> — Cek status koneksi layanan AbsensiCipta\n";
            $msg .= "├─ <code>/pengajuan</code> — Antrean pengajuan penarikan divisi Anda\n";
            $msg .= "├─ <code>/saldo</code> — Cek total saldo syirkah divisi Anda\n";
            $msg .= "├─ <code>/id</code> — Salin Telegram Chat ID Anda\n";
            $msg .= "└─ <code>/batal</code> — Batalkan proses input alasan penolakan\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '⏳ Antrean Pengajuan Divisi', 'callback_data' => 'cmd_pending'],
                        ['text' => '📊 Saldo Divisi', 'callback_data' => 'cmd_saldo'],
                    ],
                    [
                        ['text' => '⚡ Cek Status Layanan', 'callback_data' => 'cmd_status'],
                        ['text' => '🆔 Chat ID Saya', 'callback_data' => 'cmd_id'],
                    ],
                    [
                        ['text' => '🏠 Menu Utama', 'callback_data' => 'cmd_menu'],
                        ['text' => '🌐 Web Dashboard Divisi', 'url' => $appUrl . '/payroll/saving-transactions?activeTab=withdrawals'],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
            return;
        }

        // 3. REGULAR USER / KARYAWAN GUIDE
        if ($isRegularUser) {
            $msg = "👤 <b>PANDUAN & BANTUAN BOT — KARYAWAN</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "Selamat datang! Bot ini terhubung langsung dengan akun Absensi & Syirkah Cipta Grafika Anda.\n\n";

            $msg .= "📋 <b>FITUR & PANDUAN PENGGUNAAN:</b>\n";
            $msg .= "<b>1. Cek Saldo Syirkah Real-Time</b> 💰\n";
            $msg .= "   • Ketik <b>/saldo</b> atau klik tombol di bawah untuk melihat rincian Saldo Wajib, Saldo SSR/Sukarela, dan total akumulasi simpanan Anda.\n\n";

            $msg .= "<b>2. Pengajuan Penarikan Dana Syirkah</b> 📝\n";
            $msg .= "   • Pengajuan dilakukan melalui portal Web AbsensiCipta pada menu <b>Syirkah > Pengajuan Penarikan</b>.\n";
            $msg .= "   • Pengajuan akan diverifikasi oleh Manajer Divisi dan disetujui oleh Owner.\n\n";

            $msg .= "<b>3. Notifikasi Otomatis Real-Time</b> 🔔\n";
            $msg .= "   • Bot akan otomatis mengirimkan pesan konfirmasi setiap kali ada perubahan status pengajuan (Verifikasi, Approval, maupun Pencairan Transfer PAID).\n\n";

            $msg .= "⚡ <b>DAFTAR PERINTAH BOT:</b>\n";
            $msg .= "├─ <code>/start</code> — Menu utama bot\n";
            $msg .= "├─ <code>/help</code> — Panduan penggunaan bot\n";
            $msg .= "├─ <code>/saldo</code> — Rincian saldo syirkah pribadi Anda\n";
            $msg .= "├─ <code>/status</code> — Cek status koneksi layanan AbsensiCipta\n";
            $msg .= "└─ <code>/id</code> — Salin Chat ID Telegram Anda\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Cek Saldo Saya', 'callback_data' => 'cmd_saldo'],
                        ['text' => '⚡ Cek Status Layanan', 'callback_data' => 'cmd_status'],
                    ],
                    [
                        ['text' => '🏠 Menu Utama', 'callback_data' => 'cmd_menu'],
                        ['text' => '🆔 Salin Chat ID', 'callback_data' => 'cmd_id'],
                    ],
                    [
                        ['text' => '🌐 Buka Web AbsensiCipta', 'url' => $appUrl],
                    ],
                ],
            ];

            TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
            return;
        }

        // 4. GUEST / UNREGISTERED GUIDE
        $msg = "👋 <b>PANDUAN INTEGRASI AKUN — CETAKIABOT</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Akun Telegram Anda saat ini <b>belum terhubung</b> dengan akun karyawan di AbsensiCipta.\n\n";

        $msg .= "📌 <b>CARA MENGHUBUNGKAN AKUN:</b>\n";
        $msg .= "1. Salin Telegram Chat ID Anda: <code>{$fromId}</code> <i>(Klik untuk salin)</i>\n";
        $msg .= "2. Buka Web <b>AbsensiCipta > Profil / Pengaturan Akun</b>.\n";
        $msg .= "3. Tempelkan Chat ID <code>{$fromId}</code> atau Username <code>@" . htmlspecialchars($username ?? 'username') . "</code> pada kolom Telegram.\n";
        $msg .= "4. Simpan profil. Bot akan otomatis mengenali nama dan hak akses Anda!\n\n";

        $msg .= "⚡ <b>PERINTAH YANG DAPAT DIGUNAKAN:</b>\n";
        $msg .= "├─ <code>/start</code> — Memulai interaksi dengan bot\n";
        $msg .= "├─ <code>/help</code> — Panduan integrasi bot\n";
        $msg .= "├─ <code>/status</code> — Cek status koneksi ke AbsensiCipta\n";
        $msg .= "└─ <code>/id</code> — Salin Chat ID Telegram Anda\n";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🆔 Salin Chat ID', 'callback_data' => 'cmd_id'],
                    ['text' => '⚡ Cek Status Layanan', 'callback_data' => 'cmd_status'],
                ],
                [
                    ['text' => '🏠 Menu Utama', 'callback_data' => 'cmd_menu'],
                    ['text' => '🌐 Buka Web AbsensiCipta', 'url' => $appUrl],
                ],
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
    }

    /**
     * Send or update comprehensive, real-time Service & Connection Status with AbsensiCipta.
     */
    protected static function sendServiceStatusMessage($chatId, ?User $user, $fromId, ?string $username, ?int $editMessageId = null): void
    {
        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');
        $appName = config('app.name', 'Absensi Cipta Grafika');
        $appEnv = config('app.env', 'production');
        $cacheDriver = config('cache.default', 'file');

        // 1. Check Database Health & Latency
        $dbStart = microtime(true);
        $dbStatus = '❌ Terputus';
        $dbDriver = config('database.default', 'mysql');

        try {
            DB::connection()->getPdo();
            $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
            $dbStatus = "✅ Terhubung ({$dbDriver} — {$dbLatency} ms)";
        } catch (\Throwable $e) {
            $dbStatus = "❌ Gagal: " . htmlspecialchars(substr($e->getMessage(), 0, 45));
        }

        // 2. Check Bot Token Configuration
        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $botTokenStatus = !empty($botToken) ? '✅ Terkonfigurasi & Aktif' : '❌ Belum Dikonfigurasi';

        // 3. User Identity & Link Status
        if ($user) {
            $user->loadMissing('division');
            $userLinkStatus = "✅ Terhubung Aktif";
            $userName = htmlspecialchars($user->name);
            $userNip = htmlspecialchars($user->nip ?? '-');
            $userRole = strtoupper($user->group);
            $userDivision = htmlspecialchars($user->division?->name ?? 'Semua Divisi (Global Scope)');
        } else {
            $userLinkStatus = "⚠️ Belum Terhubung (Tamu)";
            $userName = "Belum Terdaftar";
            $userNip = "-";
            $userRole = "GUEST / UNLINKED";
            $userDivision = "-";
        }

        $nowFormatted = now()->translatedFormat('d F Y, H:i:s') . ' WIB';
        $timeOnly = now()->format('H:i:s') . ' WIB';

        $msg = "⚡ <b>STATUS KONEKSI & LAYANAN ABSENSICIPTA</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $msg .= "🖥️ <b>Layanan Sistem AbsensiCipta:</b>\n";
        $msg .= "├─ 🌐 <b>Host URL</b>      : <code>{$appUrl}</code>\n";
        $msg .= "├─ ⚙️ <b>Environment</b>   : <b>{$appEnv}</b> (Laravel " . app()->version() . " / PHP " . PHP_VERSION . ")\n";
        $msg .= "├─ 💾 <b>Database</b>      : {$dbStatus}\n";
        $msg .= "├─ 📦 <b>Cache Engine</b>  : <code>{$cacheDriver}</code>\n";
        $msg .= "└─ 🕒 <b>Waktu Server</b>  : {$nowFormatted}\n\n";

        $msg .= "🤖 <b>Layanan Telegram Bot (CetakiaBot):</b>\n";
        $msg .= "├─ 🔑 <b>Bot Token</b>     : {$botTokenStatus}\n";
        $msg .= "└─ 📡 <b>Status Layanan</b> : ✅ Online & Responsif\n\n";

        $msg .= "👤 <b>Status Akun Anda:</b>\n";
        $msg .= "├─ 🔗 <b>Koneksi Akun</b>  : <b>{$userLinkStatus}</b>\n";
        $msg .= "├─ 👤 <b>Nama Karyawan</b> : <b>{$userName}</b>\n";
        $msg .= "├─ 🆔 <b>NIP</b>           : {$userNip}\n";
        $msg .= "├─ 🎖️ <b>Hak Akses / Role</b> : <b>{$userRole}</b>\n";
        $msg .= "├─ 🏢 <b>Divisi</b>        : {$userDivision}\n";
        $msg .= "├─ 💬 <b>Chat ID</b>       : <code>{$fromId}</code>\n";
        $msg .= "└─ 🏷️ <b>Username</b>      : @" . htmlspecialchars($username ?? 'none') . "\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "<i>Pemeriksaan real-time pada {$timeOnly}.</i>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Refresh Status', 'callback_data' => 'refresh_status'],
                    ['text' => '📖 Panduan / Help', 'callback_data' => 'cmd_help'],
                ],
                [
                    ['text' => '🏠 Menu Utama', 'callback_data' => 'cmd_menu'],
                    ['text' => '🌐 Web AbsensiCipta', 'url' => $appUrl],
                ],
            ],
        ];

        if ($editMessageId) {
            $edited = TelegramNotificationService::editMessageText($chatId, $editMessageId, $msg, $keyboard);
            if (!$edited) {
                // In case Telegram returns false if content hasn't changed
                TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
            }
        } else {
            TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
        }
    }
}
