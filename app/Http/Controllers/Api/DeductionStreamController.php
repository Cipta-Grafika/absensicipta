<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Livewire\ScanComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeductionStreamController extends Controller
{
    /**
     * Stream realtime deduction updates using lightweight, non-blocking Server-Sent Events (SSE).
     */
    public function stream(Request $request): StreamedResponse
    {
        // Release session lock immediately to prevent blocking concurrent HTTP requests
        if (session()->isStarted()) {
            session()->save();
        }

        $user = Auth::user();

        return response()->stream(function () use ($user) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (!$user) {
                echo "event: error\n";
                echo "data: " . json_encode(['message' => 'Unauthorized']) . "\n\n";
                flush();
                return;
            }

            $userId = $user->id;
            $currentGlobal = (float) Cache::get('deduction_last_updated_global', 0.0);
            $currentPersonal = (float) Cache::get('deduction_last_updated_' . $userId, 0.0);
            $lastUpdated = max($currentGlobal, $currentPersonal);

            $scanComponent = new ScanComponent();
            $data = $scanComponent->calculateRealtimeDeductionData($user);

            echo "event: deduction-update\n";
            echo "data: " . json_encode([
                'timestamp' => $lastUpdated,
                'total' => $data['total'],
                'period' => $data['period'],
                'is_payroll_final' => $data['is_payroll_final'] ?? false,
                'items_count' => count($data['items'] ?? []),
            ]) . "\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'close',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
