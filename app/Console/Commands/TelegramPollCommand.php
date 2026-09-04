<?php

namespace App\Console\Commands;

use App\Services\TelegramBotHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--once : Run once and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll incoming Telegram messages and interactive button callbacks in real-time';

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

        $this->info('🤖 Telegram Bot Poller is running for CetakiaBot...');
        $this->info('Press Ctrl+C to stop.');

        $offset = 0;
        $isOnce = $this->option('once');

        while (true) {
            try {
                $response = Http::timeout(20)->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 5,
                    'allowed_updates' => json_encode(['message', 'callback_query']),
                ]);

                if ($response->successful()) {
                    $result = $response->json('result') ?? [];

                    foreach ($result as $update) {
                        $updateId = $update['update_id'] ?? null;
                        if ($updateId) {
                            $offset = $updateId + 1;
                        }

                        $type = isset($update['callback_query']) ? 'BUTTON_CLICK' : (isset($update['message']) ? 'MESSAGE' : 'OTHER');
                        $sender = $update['message']['from']['username'] ?? $update['callback_query']['from']['username'] ?? 'User';
                        $this->line("<comment>[{$type}]</comment> from @{$sender}");

                        try {
                            TelegramBotHandler::handleUpdate($update);
                        } catch (\Throwable $e) {
                            $this->error('Handler Error: ' . $e->getMessage());
                            Log::error('Telegram Poller Handler Error: ' . $e->getMessage(), ['update' => $update]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Sleep briefly on network error
                sleep(2);
            }

            if ($isOnce) {
                break;
            }

            usleep(300000); // 300ms pause between polling cycles
        }

        return Command::SUCCESS;
    }
}
