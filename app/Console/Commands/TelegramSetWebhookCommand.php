<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {--url= : Custom webhook URL} {--delete : Delete existing webhook} {--info : Show webhook info only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set, delete, or inspect the Telegram Bot Webhook endpoint';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        if (empty($botToken)) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env!');
            return Command::FAILURE;
        }

        // 1. Info Only
        if ($this->option('info')) {
            $res = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            $this->info('Telegram Webhook Info:');
            $this->line(json_encode($res->json('result'), JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // 2. Delete Webhook
        if ($this->option('delete')) {
            $res = Http::post("https://api.telegram.org/bot{$botToken}/deleteWebhook", [
                'drop_pending_updates' => false,
            ]);
            $this->info('Delete Webhook Response: ' . $res->body());
            return Command::SUCCESS;
        }

        // 3. Set Webhook
        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://digitalprint.biz.id')), '/');
        $webhookUrl = $this->option('url') ?: $appUrl . '/api/telegram/webhook';

        $this->info("Setting Telegram Webhook to: {$webhookUrl}");

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $webhookUrl,
            'allowed_updates' => json_encode(['message', 'callback_query']),
        ]);

        if ($response->successful() && $response->json('ok')) {
            $this->info('✅ ' . ($response->json('description') ?? 'Webhook was set successfully!'));
            return Command::SUCCESS;
        }

        $this->error('❌ Failed to set webhook: ' . $response->body());
        return Command::FAILURE;
    }
}
