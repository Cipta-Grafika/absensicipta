<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaderboardStreamController extends Controller
{
    /**
     * Handle Server-Sent Events (SSE) stream for real-time leaderboard updates.
     */
    public function stream(Request $request): StreamedResponse
    {
        $period = $request->query('period', date('Y-m'));

        return response()->stream(function () use ($period) {
            $startTime = time();
            $lastSeen = 0;

            // Release session lock immediately so concurrent web requests and Livewire updates do not block
            if (session()->isStarted()) {
                session()->save();
            }

            // Run for up to 30 seconds per stream request (EventSource automatically reconnects seamlessly)
            while (time() - $startTime < 30) {
                if (connection_aborted()) {
                    break;
                }

                $currentGlobal = (int) Cache::get('leaderboard_last_updated_global', 0);
                $currentPeriod = (int) Cache::get('leaderboard_last_updated_' . $period, 0);

                $latestTimestamp = max($currentGlobal, $currentPeriod);

                if ($latestTimestamp > $lastSeen) {
                    $lastSeen = $latestTimestamp;
                    echo "event: leaderboard_updated\n";
                    echo 'data: ' . json_encode([
                        'period' => $period,
                        'timestamp' => $latestTimestamp,
                    ]) . "\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                // Heartbeat to prevent proxy timeouts
                echo ": keepalive\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
