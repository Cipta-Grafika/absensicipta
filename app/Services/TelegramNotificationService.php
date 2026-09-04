<?php

namespace App\Services;

use App\Models\SavingSummary;
use App\Models\SavingWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    /**
     * Send a raw HTML formatted message to Telegram chat(s) with optional inline keyboard.
     */
    public static function sendMessage(string|array $chatIds, string $message, ?array $replyMarkup = null): bool
    {
        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        if (empty($botToken)) {
            Log::info('TelegramNotificationService: TELEGRAM_BOT_TOKEN is not configured.');
            return false;
        }

        $chatIdList = is_array($chatIds) ? $chatIds : explode(',', (string) $chatIds);
        $chatIdList = array_unique(array_filter(array_map('trim', $chatIdList)));

        if (empty($chatIdList)) {
            return false;
        }

        $success = true;

        foreach ($chatIdList as $chatId) {
            if (empty($chatId)) continue;

            try {
                $payload = [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ];

                if (!empty($replyMarkup)) {
                    $payload['reply_markup'] = json_encode($replyMarkup);
                }

                $response = Http::timeout(6)->asForm()->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);

                if (!$response->successful()) {
                    Log::warning('Telegram Notification failed to send', [
                        'chat_id' => $chatId,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    $success = false;
                }
            } catch (\Throwable $e) {
                Log::error('Telegram Notification Exception: ' . $e->getMessage(), [
                    'chat_id' => $chatId,
                ]);
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Edit an existing Telegram message in place.
     */
    public static function editMessageText(string|int $chatId, int $messageId, string $newText, ?array $replyMarkup = null): bool
    {
        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        if (empty($botToken)) return false;

        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $newText,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ];

            if ($replyMarkup !== null) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::timeout(6)->asForm()->post("https://api.telegram.org/bot{$botToken}/editMessageText", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram editMessageText Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Answer a Telegram callback query (shows popup or toast to user).
     */
    public static function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        if (empty($botToken)) return false;

        try {
            $response = Http::timeout(5)->asForm()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram answerCallbackQuery Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification for a new PENDING withdrawal request to Manager / Division Admin.
     * Includes interactive [Setujui (ACC)] and [Tolak] inline buttons.
     */
    public static function notifyPendingWithdrawal(SavingWithdrawal $withdrawal): void
    {
        $withdrawal->loadMissing(['user.division', 'masterSaving']);
        $user = $withdrawal->user;
        $userDivisionId = $user?->division_id;
        $division = $user?->division?->name ?? 'Semua Divisi';
        $program = $withdrawal->masterSaving?->savings_name ?? 'Syirkah Umum';

        // 1. Resolve Dynamic Recipient Targets (ONLY Admin of employee's division)
        $targetIds = [];

        $adminQuery = User::where('group', 'admin')
            ->when($userDivisionId, function ($q) use ($userDivisionId) {
                $q->where('division_id', $userDivisionId);
            })
            ->where(function ($q) {
                $q->whereNotNull('chat_code')->where('chat_code', '!=', '')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('telegram')->where('telegram', '!=', '');
                  });
            })
            ->get();

        foreach ($adminQuery as $admin) {
            if (!empty($admin->chat_code)) {
                $targetIds[] = trim($admin->chat_code);
            }
        }

        // Fallback to .env TELEGRAM_ADMIN_CHAT_ID if no database users have chat_code
        if (empty($targetIds)) {
            $envChatId = config('services.telegram.admin_chat_id', env('TELEGRAM_ADMIN_CHAT_ID'));
            if (!empty($envChatId)) {
                $targetIds[] = trim($envChatId);
            }
        }

        $targetIds = array_unique(array_filter($targetIds));
        if (empty($targetIds)) {
            Log::info('TelegramNotificationService: No valid Telegram Chat ID found for Division Admin.');
            return;
        }

        // 2. Fetch user's current summary balance
        $summary = SavingSummary::where('user_id', $withdrawal->user_id)
            ->where('savings_id', $withdrawal->savings_id)
            ->first();
        $currMandatory = (float) ($summary?->total_mandatory ?? 0);
        $currSecondary = (float) ($summary?->total_secondary ?? 0);
        $currTotal = $currMandatory + $currSecondary;

        $typeLabel = $withdrawal->withdrawal_type_label;
        $totalNominal = number_format($withdrawal->total_amount, 0, ',', '.');
        $mandNominal = number_format($withdrawal->mandatory_amount, 0, ',', '.');
        $secNominal = number_format($withdrawal->secondary_amount, 0, ',', '.');

        $curMandNominal = number_format($currMandatory, 0, ',', '.');
        $curSecNominal = number_format($currSecondary, 0, ',', '.');
        $curTotNominal = number_format($currTotal, 0, ',', '.');

        $dateFormatted = $withdrawal->created_at 
            ? $withdrawal->created_at->translatedFormat('d F Y, H:i') . ' WIB'
            : now()->translatedFormat('d F Y, H:i') . ' WIB';

        $baseUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $actionUrl = $baseUrl . '/payroll/saving-transactions?activeTab=withdrawals';

        // 3. Construct Message
        $message = "🔔 <b>PENGAJUAN PENARIKAN SYIRKAH BARU (PENDING)</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "👤 <b>Karyawan</b> : " . htmlspecialchars($user?->name ?? '-') . " (NIP: " . htmlspecialchars($user?->nip ?? '-') . ")\n";
        $message .= "🏢 <b>Divisi</b>   : " . htmlspecialchars($division) . "\n";
        $message .= "🏦 <b>Program</b>  : " . htmlspecialchars($program) . "\n";
        $message .= "📑 <b>Opsi</b>     : <b>" . htmlspecialchars($typeLabel) . "</b>\n";
        $message .= "💰 <b>Total Pengajuan</b> : <b>Rp {$totalNominal}</b>\n";
        if ($withdrawal->mandatory_amount > 0) {
            $message .= "   ├─ Syirkah Wajib : Rp {$mandNominal}\n";
        }
        if ($withdrawal->secondary_amount > 0) {
            $message .= "   └─ Sukarela (SSR) : Rp {$secNominal}\n";
        }

        $message .= "\n📊 <b>Saldo Tabungan Saat Ini:</b>\n";
        $message .= "   ├─ Saldo Wajib : Rp {$curMandNominal}\n";
        $message .= "   ├─ Saldo SSR   : Rp {$curSecNominal}\n";
        $message .= "   └─ Total Saldo : <b>Rp {$curTotNominal}</b>\n";

        if (!empty($withdrawal->reason)) {
            $message .= "\n📝 <b>Keperluan / Alasan:</b>\n";
            $message .= "<i>\"" . htmlspecialchars($withdrawal->reason) . "\"</i>\n";
        }

        $message .= "\n📅 <b>Waktu</b>    : {$dateFormatted}\n";
        $message .= "🔗 <b>Menu Approval</b> : <a href=\"{$actionUrl}\">{$actionUrl}</a>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        // 4. Interactive Inline Keyboard (Direct ACC / Reject from Telegram!)
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '✅ Setujui (ACC)',
                        'callback_data' => 'acc_wd_' . $withdrawal->id,
                    ],
                    [
                        'text' => '❌ Tolak',
                        'callback_data' => 'rej_wd_' . $withdrawal->id,
                    ],
                ],
                [
                    [
                        'text' => '🌐 Buka di Web App',
                        'url' => $actionUrl,
                    ],
                ],
            ],
        ];

        self::sendMessage($targetIds, $message, $keyboard);
    }

    /**
     * Send notification for an ACCEPTED withdrawal request to all Owner group users.
     * Includes interactive [Tandai Dibayar (PAID)] inline button.
     */
    public static function notifyAcceptedWithdrawal(SavingWithdrawal $withdrawal): void
    {
        $withdrawal->loadMissing(['user.division', 'user.paymentMethod', 'masterSaving', 'approver']);
        $user = $withdrawal->user;
        $division = $user?->division?->name ?? 'Semua Divisi';
        $program = $withdrawal->masterSaving?->savings_name ?? 'Syirkah Umum';
        $approverName = $withdrawal->approver?->name ?? 'Admin Divisi';

        // 1. Resolve Dynamic Recipient Targets (All users in OWNER group)
        $targetIds = [];

        $ownerQuery = User::where('group', 'owner')
            ->where(function ($q) {
                $q->whereNotNull('chat_code')->where('chat_code', '!=', '')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('telegram')->where('telegram', '!=', '');
                  });
            })
            ->get();

        foreach ($ownerQuery as $ownerUser) {
            if (!empty($ownerUser->chat_code)) {
                $targetIds[] = trim($ownerUser->chat_code);
            }
        }

        // Fallback to .env TELEGRAM_OWNER_CHAT_ID if no database users have chat_code
        if (empty($targetIds)) {
            $envChatId = config('services.telegram.owner_chat_id', env('TELEGRAM_OWNER_CHAT_ID'));
            if (!empty($envChatId)) {
                $targetIds[] = trim($envChatId);
            }
        }

        $targetIds = array_unique(array_filter($targetIds));
        if (empty($targetIds)) {
            Log::info('TelegramNotificationService: No valid Telegram Chat ID found for Owner.');
            return;
        }

        // 2. Payment Method info
        $paymentMethod = $user?->paymentMethod;
        $bankName = $paymentMethod?->payment_name ?? 'Belum Diatur';
        $bankAccount = $paymentMethod?->bank_account ?? '-';
        $accountName = $paymentMethod?->account_name ?? ($user?->name ?? '-');

        // Remaining balance after deduction
        $summary = SavingSummary::where('user_id', $withdrawal->user_id)
            ->where('savings_id', $withdrawal->savings_id)
            ->first();
        $remMandatory = (float) ($summary?->total_mandatory ?? 0);
        $remSecondary = (float) ($summary?->total_secondary ?? 0);
        $remTotal = $remMandatory + $remSecondary;

        $typeLabel = $withdrawal->withdrawal_type_label;
        $totalNominal = number_format($withdrawal->total_amount, 0, ',', '.');
        $mandNominal = number_format($withdrawal->mandatory_amount, 0, ',', '.');
        $secNominal = number_format($withdrawal->secondary_amount, 0, ',', '.');

        $remMandNominal = number_format($remMandatory, 0, ',', '.');
        $remSecNominal = number_format($remSecondary, 0, ',', '.');
        $remTotNominal = number_format($remTotal, 0, ',', '.');

        $approvedDate = $withdrawal->approved_at 
            ? $withdrawal->approved_at->translatedFormat('d F Y, H:i') . ' WIB'
            : now()->translatedFormat('d F Y, H:i') . ' WIB';

        $baseUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $actionUrl = $baseUrl . '/payroll/saving-transactions?activeTab=withdrawals';

        // 3. Construct Message
        $message = "✅ <b>PENGAJUAN PENARIKAN SYIRKAH DISETUJUI (ACCEPTED)</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Pengajuan penarikan dana telah diverifikasi oleh Manager Divisi dan siap untuk dicairkan / ditransfer.\n\n";
        $message .= "👤 <b>Karyawan</b>  : " . htmlspecialchars($user?->name ?? '-') . " (NIP: " . htmlspecialchars($user?->nip ?? '-') . ")\n";
        $message .= "🏢 <b>Divisi</b>    : " . htmlspecialchars($division) . "\n";
        $message .= "🏦 <b>Program</b>   : " . htmlspecialchars($program) . "\n";
        $message .= "✍️ <b>Disetujui</b> : " . htmlspecialchars($approverName) . " ({$approvedDate})\n";
        $message .= "📑 <b>Opsi</b>      : " . htmlspecialchars($typeLabel) . "\n";
        $message .= "💵 <b>Nominal Siap Cair</b> : <b>Rp {$totalNominal}</b>\n";
        if ($withdrawal->mandatory_amount > 0) {
            $message .= "   ├─ Syirkah Wajib : Rp {$mandNominal}\n";
        }
        if ($withdrawal->secondary_amount > 0) {
            $message .= "   └─ Sukarela (SSR) : Rp {$secNominal}\n";
        }

        $message .= "\n💳 <b>Rekening Pembayaran Karyawan:</b>\n";
        $message .= "   ├─ <b>Bank / Provider</b> : <b>" . htmlspecialchars($bankName) . "</b>\n";
        $message .= "   ├─ <b>No. Rekening</b>    : <code>" . htmlspecialchars($bankAccount) . "</code>\n";
        $message .= "   └─ <b>Atas Nama</b>       : " . htmlspecialchars($accountName) . "\n";

        $message .= "\n📉 <b>Sisa Saldo Setelah Dipotong:</b>\n";
        $message .= "   ├─ Saldo Wajib : Rp {$remMandNominal}\n";
        $message .= "   ├─ Saldo SSR   : Rp {$remSecNominal}\n";
        $message .= "   └─ Total Saldo : <b>Rp {$remTotNominal}</b>\n";

        if (!empty($withdrawal->reason)) {
            $message .= "\n📝 <b>Keperluan:</b>\n";
            $message .= "<i>\"" . htmlspecialchars($withdrawal->reason) . "\"</i>\n";
        }

        $message .= "🔗 <b>Menu Mutasi</b> : <a href=\"{$actionUrl}\">{$actionUrl}</a>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        // 4. Interactive Inline Keyboard (Direct Mark as PAID / Open Web)
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '💵 Tandai Telah Dibayar (PAID)',
                        'callback_data' => 'paid_wd_' . $withdrawal->id,
                    ],
                ],
                [
                    [
                        'text' => '🌐 Buka di Web App',
                        'url' => $actionUrl,
                    ],
                ],
            ],
        ];

        self::sendMessage($targetIds, $message, $keyboard);
    }

    /**
     * Send notification for a PAID withdrawal request to Employee and Division Admin.
     */
    public static function notifyPaidWithdrawal(SavingWithdrawal $withdrawal): void
    {
        $withdrawal->loadMissing(['user.division', 'user.paymentMethod', 'masterSaving', 'payer', 'approver']);
        $user = $withdrawal->user;
        $division = $user?->division?->name ?? 'Semua Divisi';
        $program = $withdrawal->masterSaving?->savings_name ?? 'Syirkah Umum';
        $payerName = $withdrawal->payer?->name ?? 'Owner / Finance';
        $totalNominal = number_format($withdrawal->total_amount, 0, ',', '.');
        $paidDate = $withdrawal->paid_at 
            ? $withdrawal->paid_at->translatedFormat('d F Y, H:i') . ' WIB'
            : now()->translatedFormat('d F Y, H:i') . ' WIB';

        $targetIds = [];

        // 1. Target: The Employee
        if (!empty($user?->chat_code)) {
            $targetIds[] = trim($user->chat_code);
        }

        // 2. Target: Division Admin
        if ($user?->division_id) {
            $adminIds = User::where('group', 'admin')
                ->where('division_id', $user->division_id)
                ->whereNotNull('chat_code')
                ->where('chat_code', '!=', '')
                ->pluck('chat_code')
                ->toArray();
            $targetIds = array_merge($targetIds, $adminIds);
        }

        $targetIds = array_unique(array_filter($targetIds));
        if (empty($targetIds)) return;

        $message = "💵 <b>PENCAIRAN SYIRKAH SELESAI DIBAYARKAN (PAID)</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Dana penarikan syirkah telah selesai ditransfer / dibayarkan oleh Owner/Finance.\n\n";
        $message .= "👤 <b>Karyawan</b>  : " . htmlspecialchars($user?->name ?? '-') . " (NIP: " . htmlspecialchars($user?->nip ?? '-') . ")\n";
        $message .= "🏢 <b>Divisi</b>    : " . htmlspecialchars($division) . "\n";
        $message .= "🏦 <b>Program</b>   : " . htmlspecialchars($program) . "\n";
        $message .= "💰 <b>Nominal</b>   : <b>Rp {$totalNominal}</b>\n";
        $message .= "🏦 <b>Dibayar Oleh</b> : " . htmlspecialchars($payerName) . "\n";
        $message .= "📅 <b>Waktu Bayar</b> : {$paidDate}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Status: <b>SELESAI (PAID)</b> ✨";

        self::sendMessage($targetIds, $message);
    }
}
