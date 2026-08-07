<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LeaderboardStreamController extends Controller
{
    /**
     * Handle instant JSON response for leaderboard updates without thread blocking.
     */
    public function stream(Request $request)
    {
        if (session()->isStarted()) {
            session()->save();
        }

        $period = $request->query('period', date('Y-m'));
        $currentGlobal = (int) Cache::get('leaderboard_last_updated_global', 0);
        $currentPeriod = (int) Cache::get('leaderboard_last_updated_' . $period, 0);

        return response()->json([
            'period' => $period,
            'timestamp' => max($currentGlobal, $currentPeriod),
        ]);
    }
}
