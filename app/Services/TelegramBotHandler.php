<?php

namespace App\Services;

use App\Models\SavingSummary;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\User;
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

        $command = strtolower(explode(' ', $text)[0] ?? '');

        switch ($command) {
            case '/start':
            case '/menu':
            case '/help':
            case '/bantuan':
                self::sendWelcomeMessage($chatId, $fromId, $username, $firstName, $user);
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
        $originalText = $callbackQuery['message']['text'] ?? '';

        // Find approving user
        $approverUser = User::where('chat_code', (string) $fromId)
            ->orWhere(function ($q) use ($fromUsername) {
                if ($fromUsername) {
                    $q->where('telegram', '@' . $fromUsername)
                      ->orWhere('telegram', $fromUsername);
                }
            })
            ->first() ?? User::where('group', 'superadmin')->first();

        // Menu callbacks
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

        // Action: Approve Withdrawal
        if (str_starts_with($data, 'acc_wd_')) {
            $withdrawalId = substr($data, 7);
            $withdrawal = SavingWithdrawal::with('user')->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan atau sudah dihapus.', true);
                return;
            }

            if ($withdrawal->status === 'accepted' || $withdrawal->status === 'paid') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah disetujui sebelumnya.', true);
                return;
            }

            if ($withdrawal->status === 'rejected') {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan ini sudah ditolak sebelumnya.', true);
                return;
            }

            try {
                $approverId = $approverUser?->id ?? User::where('group', 'superadmin')->value('id');
                SavingTransactionService::approveWithdrawalRequest($withdrawalId, $approverId);

                TelegramNotificationService::answerCallbackQuery($callbackId, '✅ Berhasil disetujui! Saldo mutasi syirkah telah dipotong.', true);

                // Edit the original Telegram message to show approved status
                if ($messageId) {
                    $dateNow = now()->translatedFormat('d M Y, H:i') . ' WIB';
                    $updatedText = "✅ <b>PENGAJUAN PENARIKAN TELAH DISETUJUI (ACCEPTED)</b>\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "👤 <b>Karyawan</b> : " . htmlspecialchars($withdrawal->user?->name ?? '-') . "\n";
                    $updatedText .= "💰 <b>Nominal</b>  : <b>Rp " . number_format($withdrawal->total_amount, 0, ',', '.') . "</b>\n";
                    $updatedText .= "✍️ <b>Disetujui Oleh</b> : " . htmlspecialchars($fromFirstName) . " (@" . htmlspecialchars($fromUsername ?? 'admin') . ")\n";
                    $updatedText .= "📅 <b>Waktu ACC</b> : {$dateNow}\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "Status: <b>Menunggu Pembayaran (PAID)</b> oleh Owner/Finance.";

                    TelegramNotificationService::editMessageText($chatId, $messageId, $updatedText);
                }
            } catch (\Throwable $e) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Gagal menyetujui: ' . $e->getMessage(), true);
            }
            return;
        }

        // Action: Reject Withdrawal
        if (str_starts_with($data, 'rej_wd_')) {
            $withdrawalId = substr($data, 7);
            $withdrawal = SavingWithdrawal::with('user')->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan.', true);
                return;
            }

            try {
                $approverId = $approverUser?->id ?? User::where('group', 'superadmin')->value('id');
                SavingTransactionService::rejectWithdrawalRequest($withdrawalId, $approverId, 'Ditolak via Telegram oleh @' . ($fromUsername ?? 'admin'));

                TelegramNotificationService::answerCallbackQuery($callbackId, '❌ Pengajuan telah ditolak.', true);

                if ($messageId) {
                    $dateNow = now()->translatedFormat('d M Y, H:i') . ' WIB';
                    $updatedText = "❌ <b>PENGAJUAN PENARIKAN TELAH DITOLAK (REJECTED)</b>\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "👤 <b>Karyawan</b> : " . htmlspecialchars($withdrawal->user?->name ?? '-') . "\n";
                    $updatedText .= "💰 <b>Nominal</b>  : Rp " . number_format($withdrawal->total_amount, 0, ',', '.') . "\n";
                    $updatedText .= "🚫 <b>Ditolak Oleh</b> : " . htmlspecialchars($fromFirstName) . " (@" . htmlspecialchars($fromUsername ?? 'admin') . ")\n";
                    $updatedText .= "📅 <b>Waktu</b> : {$dateNow}\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "Status: <b>DITOLAK (Saldo tidak terpotong)</b>";

                    TelegramNotificationService::editMessageText($chatId, $messageId, $updatedText);
                }
            } catch (\Throwable $e) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Gagal menolak: ' . $e->getMessage(), true);
            }
            return;
        }

        // Action: Mark as Paid
        if (str_starts_with($data, 'paid_wd_')) {
            $withdrawalId = substr($data, 8);
            $withdrawal = SavingWithdrawal::with('user')->find($withdrawalId);

            if (!$withdrawal) {
                TelegramNotificationService::answerCallbackQuery($callbackId, 'Pengajuan tidak ditemukan.', true);
                return;
            }

            try {
                $payerId = $approverUser?->id ?? User::where('group', 'owner')->value('id') ?? User::where('group', 'superadmin')->value('id');
                SavingTransactionService::markAsPaidWithdrawalRequest($withdrawalId, $payerId);

                TelegramNotificationService::answerCallbackQuery($callbackId, '💰 Berhasil ditandai telah dibayarkan (PAID)!', true);

                if ($messageId) {
                    $dateNow = now()->translatedFormat('d M Y, H:i') . ' WIB';
                    $updatedText = "💵 <b>PENARIKAN SELESAI DIBAYARKAN (PAID)</b>\n";
                    $updatedText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $updatedText .= "👤 <b>Karyawan</b> : " . htmlspecialchars($withdrawal->user?->name ?? '-') . "\n";
                    $updatedText .= "💰 <b>Nominal</b>  : <b>Rp " . number_format($withdrawal->total_amount, 0, ',', '.') . "</b>\n";
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
                    ['text' => '📊 Cek Saldo Syirkah', 'callback_data' => 'cmd_saldo'],
                    ['text' => '⏳ Antrean Pengajuan', 'callback_data' => 'cmd_pending'],
                ],
                [
                    ['text' => '🆔 Salin Chat ID', 'callback_data' => 'cmd_id'],
                    ['text' => '🌐 Buka Web Dashboard', 'url' => config('app.url', 'http://localhost:8000') . '/payroll/saving-transactions'],
                ],
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
    }

    protected static function sendBalanceMessage($chatId, ?User $user): void
    {
        $scopeTitle = "PERUSAHAAN (GLOBAL)";
        $scopeSubtitle = "🏢 <b>Cakupan</b> : Seluruh Divisi (Owner/Superadmin Scope)";

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
                    ['text' => '⏳ Cek Antrean Pengajuan', 'callback_data' => 'cmd_pending'],
                    ['text' => '🌐 Buka Web Mutasi', 'url' => $appUrl . '/payroll/saving-transactions'],
                ],
            ],
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
    }

    protected static function sendPendingWithdrawalsList($chatId, ?User $user): void
    {
        $query = SavingWithdrawal::with(['user.division', 'masterSaving'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        if ($user && $user->group === 'admin' && $user->division_id) {
            $query->whereHas('user', fn($q) => $q->where('division_id', $user->division_id));
        }

        $pendingList = $query->take(5)->get();

        if ($pendingList->isEmpty()) {
            $msg = "✨ <b>TIDAK ADA ANTREAN PENGAJUAN</b>\n\n";
            $msg .= "Saat ini tidak ada pengajuan penarikan syirkah berstatus <b>PENDING</b>. Semua pengajuan telah diproses!";
            TelegramNotificationService::sendMessage($chatId, $msg);
            return;
        }

        $count = $pendingList->count();
        $msg = "⏳ <b>DAFTAR ANTREAN PENGAJUAN SYIRKAH (PENDING)</b>\n";
        $msg .= "Menampilkan {$count} pengajuan terbaru yang menunggu persetujuan:\n\n";

        $inlineButtons = [];

        foreach ($pendingList as $idx => $wd) {
            $num = $idx + 1;
            $name = $wd->user?->name ?? 'Karyawan';
            $div = $wd->user?->division?->name ?? '-';
            $nominal = number_format($wd->total_amount, 0, ',', '.');
            $type = $wd->withdrawal_type_label;
            $date = $wd->created_at ? $wd->created_at->translatedFormat('d M, H:i') : '-';

            $msg .= "<b>{$num}. {$name}</b> ({$div})\n";
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

        $keyboard = [
            'inline_keyboard' => $inlineButtons,
        ];

        TelegramNotificationService::sendMessage($chatId, $msg, $keyboard);
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
