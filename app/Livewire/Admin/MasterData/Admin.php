<?php

namespace App\Livewire\Admin\MasterData;

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Admin extends Component
{
    use WithPagination, InteractsWithBanner, WithFileUploads;

    public UserForm $form;
    public $groups = [];
    public $deleteName = null;
    public $creating = false;
    public $editing = false;
    public $confirmingDeletion = false;
    public $selectedId = null;
    public $showDetail = null;

    // Telegram Webhook & Bot Group Tester
    public bool $showTelegramModal = false;
    public string $telegramTargetGroup = '';
    public string $telegramCustomMessage = '';
    public ?array $telegramTestResult = null;
    public bool $telegramIsLoading = false;
    public array $telegramKnownGroups = [];
    public ?array $telegramWebhookInfo = null;
    public string $telegramErrorMessage = '';

    public function __construct()
    {
        $this->groups = User::$groups;
    }

    public function mount()
    {
        if (!auth()->user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya SuperAdmin yang berhak mengelola Manajemen Admin.');
        }
    }

    public function show($id)
    {
        $this->form->setUser(User::with(['adminDivisions', 'division', 'jobTitle', 'education'])->findOrFail($id));
        $this->showDetail = true;
    }

    #[On('show-creating')]
    public function showCreating()
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->creating = true;
        $this->form->password = 'admin';
        $this->form->group = 'admin';
        $this->form->division_ids = [];
    }

    public function create()
    {
        $this->form->store();
        $this->creating = false;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->editing = true;
        /** @var User $user */
        $user = User::with('adminDivisions')->findOrFail($id);
        $this->form->setUser($user);
    }

    public function update()
    {
        $this->form->update();
        $this->editing = false;
        $this->banner(__('Updated successfully.'));
    }

    public function deleteProfilePhoto()
    {
        $this->form->deleteProfilePhoto();
    }

    public function confirmDeletion($id, $name)
    {
        $this->deleteName = $name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete()
    {
        $user = User::find($this->selectedId);
        $this->form->setUser($user)->delete();
        $this->confirmingDeletion = false;
        $this->banner(__('Deleted successfully.'));
    }

    #[On('show-telegram-tester')]
    public function showTelegramTester(): void
    {
        $this->showTelegramModal = true;
        $this->telegramTestResult = null;
        $this->telegramErrorMessage = '';
        $this->refreshTelegramGroups();

        if (empty($this->telegramTargetGroup)) {
            $defaultId = Cache::get('telegram_bank_transfer_group_id')
                ?: config('services.telegram.bank_transfer_group_id')
                ?: config('services.telegram.group_chat_id')
                ?: env('TELEGRAM_BANK_TRANSFER_GROUP_ID')
                ?: env('TELEGRAM_GROUP_CHAT_ID', '');

            if (!empty($defaultId)) {
                $this->telegramTargetGroup = (string) $defaultId;
            } elseif (!empty($this->telegramKnownGroups)) {
                foreach ($this->telegramKnownGroups as $id => $grp) {
                    if (stripos($grp['title'] ?? '', 'Bank') !== false || stripos($grp['title'] ?? '', 'transfer') !== false) {
                        $this->telegramTargetGroup = (string) $id;
                        break;
                    }
                }
                if (empty($this->telegramTargetGroup)) {
                    $this->telegramTargetGroup = (string) array_key_first($this->telegramKnownGroups);
                }
            }
        }
    }

    public function closeTelegramTester(): void
    {
        $this->showTelegramModal = false;
    }

    public function refreshTelegramGroups(): void
    {
        $this->telegramKnownGroups = Cache::get('telegram_known_groups', []);
    }

    public function selectKnownGroup(string $chatId): void
    {
        $this->telegramTargetGroup = $chatId;
    }

    public function saveAsDefaultGroup(): void
    {
        $target = trim($this->telegramTargetGroup);
        if (empty($target)) {
            $this->telegramErrorMessage = 'Chat ID grup tidak boleh kosong.';
            return;
        }

        // Telegram groups strictly have negative IDs
        if (!str_starts_with($target, '-')) {
            $this->telegramErrorMessage = 'ID Grup Telegram harus berupa angka negatif yang diawali tanda minus (-), contoh: -100xxxxxxxxxx. ID positif adalah akun pengguna perorangan.';
            return;
        }

        Cache::forever('telegram_bank_transfer_group_id', $target);
        if (isset($this->telegramKnownGroups[$target]['title'])) {
            Cache::forever('telegram_bank_transfer_group_title', $this->telegramKnownGroups[$target]['title']);
        }

        $this->telegramErrorMessage = '';
        $this->banner("Chat ID grup {$target} berhasil disimpan sebagai default untuk notifikasi CG Bank transfer Request.");
    }

    public function checkWebhookInfo(): void
    {
        $this->telegramIsLoading = true;
        $this->telegramErrorMessage = '';

        try {
            $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
            if (empty($botToken)) {
                $this->telegramErrorMessage = 'TELEGRAM_BOT_TOKEN belum dikonfigurasi di file .env.';
                return;
            }

            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            $this->telegramWebhookInfo = [
                'status_code' => $response->status(),
                'ok' => $response->json('ok') ?? false,
                'data' => $response->json('result') ?? [],
                'raw' => $response->json() ?: $response->body(),
                'checked_at' => now()->format('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            $this->telegramErrorMessage = 'Gagal memeriksa Webhook Info: ' . $e->getMessage();
        } finally {
            $this->telegramIsLoading = false;
        }
    }

    public function sendTelegramGroupTest(): void
    {
        $this->telegramIsLoading = true;
        $this->telegramErrorMessage = '';
        $this->telegramTestResult = null;

        $target = trim($this->telegramTargetGroup);

        if (empty($target)) {
            $this->telegramErrorMessage = 'Silakan masukkan atau pilih ID Grup Telegram tujuan.';
            $this->telegramIsLoading = false;
            return;
        }

        // VALIDATION: Specifically for Telegram GROUP, NOT user account!
        // Telegram groups and supergroups strictly have negative IDs (e.g. -100xxxxxxxxxx or -xxxxxxxxx)
        // User private chat IDs are positive numbers (e.g. 1909551792).
        if (!str_starts_with($target, '-')) {
            $this->telegramErrorMessage = "⚠️ Target ID '{$target}' bukan merupakan ID Grup Telegram! ID akun perorangan bertanda positif. Telegram Group ID WAJIB diawali dengan tanda minus (-), contoh: -100xxxxxxxxxx. Fitur ini khusus menguji ke grup (CG Bank transfer Request), bukan ke akun users.";
            $this->telegramIsLoading = false;
            return;
        }

        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        if (empty($botToken)) {
            $this->telegramErrorMessage = 'TELEGRAM_BOT_TOKEN belum diatur pada konfigurasi aplikasi (.env).';
            $this->telegramIsLoading = false;
            return;
        }

        $adminName = auth()->user()->name ?? 'Super Admin';
        $waktuBayar = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $note = !empty($this->telegramCustomMessage) 
            ? trim($this->telegramCustomMessage) 
            : 'Uji coba pengiriman antrean pencairan syirkah ke grup transfer bank';

        $messageText = "💵 PENCAIRAN SYIRKAH (UNPAID)\n";
        $messageText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $messageText .= "Dana penarikan syirkah siap ditransfer / dibayarkan oleh Owner/Finance.\n\n";
        $messageText .= "Nama (Name)  : " . htmlspecialchars($adminName) . "\n";
        $messageText .= "NIP : 2308407\n";
        $messageText .= "Divisi    : Cipta Corp\n";
        $messageText .= "Program   : Syirkah Penuh\n";
        $messageText .= "Nominal Bayar (Transfer Amount) : Rp 2.000.000 (Pengajuan: Rp 3.000.000)\n";
        $messageText .= "Dibayar Oleh (Transfer By) : " . htmlspecialchars($adminName) . "\n";
        $messageText .= "Waktu Bayar : {$waktuBayar}\n";
        $messageText .= "Notes : \"{$note}\"\n";
        $messageText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $messageText .= "Status: UNPAID";

        $payload = [
            'chat_id' => $target,
            'text' => $messageText,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ];

        $endpoint = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $maskedEndpoint = "https://api.telegram.org/bot" . substr($botToken, 0, 8) . "..." . substr($botToken, -4) . "/sendMessage";

        $startTime = microtime(true);
        try {
            $response = Http::timeout(12)->asForm()->post($endpoint, $payload);
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            $isSuccess = $response->successful() && ($response->json('ok') ?? false);
            $responseJson = $response->json() ?? [];

            $this->telegramTestResult = [
                'success' => $isSuccess,
                'status_code' => $response->status(),
                'latency_ms' => $latencyMs,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'target_chat_id' => $target,
                'request' => [
                    'method' => 'POST',
                    'endpoint' => $maskedEndpoint,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                    ],
                    'payload' => [
                        'chat_id' => $target,
                        'text' => $messageText,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => false,
                    ],
                ],
                'response' => [
                    'status' => $response->status(),
                    'ok' => $responseJson['ok'] ?? false,
                    'body' => $responseJson ?: $response->body(),
                ],
                'details' => [
                    'message_id' => $responseJson['result']['message_id'] ?? null,
                    'chat_id' => $responseJson['result']['chat']['id'] ?? $target,
                    'chat_title' => $responseJson['result']['chat']['title'] ?? null,
                    'chat_type' => $responseJson['result']['chat']['type'] ?? null,
                    'from_bot' => ($responseJson['result']['from']['first_name'] ?? 'CetakiaBot') . ' (@' . ($responseJson['result']['from']['username'] ?? 'cetakia_bot') . ')',
                    'error_code' => $responseJson['error_code'] ?? null,
                    'description' => $responseJson['description'] ?? null,
                ],
            ];

            if ($isSuccess) {
                // Auto-save this confirmed group
                Cache::forever('telegram_bank_transfer_group_id', $target);
                if (!empty($responseJson['result']['chat']['title'])) {
                    Cache::forever('telegram_bank_transfer_group_title', $responseJson['result']['chat']['title']);
                }

                // Add to known groups
                $groups = Cache::get('telegram_known_groups', []);
                $groups[$target] = [
                    'id' => $target,
                    'title' => $responseJson['result']['chat']['title'] ?? 'CG Bank transfer Request',
                    'type' => $responseJson['result']['chat']['type'] ?? 'supergroup',
                    'updated_at' => now()->toIso8601String(),
                ];
                Cache::forever('telegram_known_groups', $groups);
                $this->refreshTelegramGroups();
                $this->banner("Pesan test berhasil dikirimkan ke grup {$target}!");
            }
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $this->telegramTestResult = [
                'success' => false,
                'status_code' => 500,
                'latency_ms' => $latencyMs,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'target_chat_id' => $target,
                'request' => [
                    'method' => 'POST',
                    'endpoint' => $maskedEndpoint,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'payload' => [
                        'chat_id' => $target,
                        'text' => $messageText,
                        'parse_mode' => 'HTML',
                    ],
                ],
                'response' => [
                    'status' => 500,
                    'ok' => false,
                    'body' => ['error' => $e->getMessage()],
                ],
                'details' => [
                    'error_code' => 500,
                    'description' => 'Terjadi exception saat menghubungi Telegram Bot API: ' . $e->getMessage(),
                ],
            ];
            $this->telegramErrorMessage = 'Exception: ' . $e->getMessage();
        } finally {
            $this->telegramIsLoading = false;
        }
    }

    public function render()
    {
        $users = User::where('group', '!=', 'user')
            ->when(auth()->user()->group === 'admin', fn($q) => $q->whereIn('division_id', auth()->user()->getAccessibleDivisionIds()))
            ->with(['division', 'adminDivisions', 'jobTitle'])
            ->orderBy('group', 'desc')
            ->paginate(20);
        return view('livewire.admin.master-data.admin', ['users' => $users]);
    }
}
