<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DeductionNotificationService
{
    /**
     * Notify that a specific user's deduction data has been updated.
     */
    public static function notify(int|string|null $userId): void
    {
        if (!$userId) {
            return;
        }

        $now = microtime(true);
        Cache::put('deduction_last_updated_' . $userId, $now, 3600);
        Cache::forget('realtime_deduction_' . $userId . '_' . date('Y-m-d'));
    }

    /**
     * Notify globally that deduction data has been recalculated or generated.
     */
    public static function notifyGlobal(): void
    {
        $now = microtime(true);
        Cache::put('deduction_last_updated_global', $now, 3600);
    }
}
