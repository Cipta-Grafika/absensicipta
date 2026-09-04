<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook updates from Telegram Bot API.
     */
    public function handle(Request $request)
    {
        $update = $request->all();
        
        if (!empty($update)) {
            try {
                TelegramBotHandler::handleUpdate($update);
            } catch (\Throwable $e) {
                Log::error('Telegram Webhook Handler Error: ' . $e->getMessage(), ['update' => $update]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
