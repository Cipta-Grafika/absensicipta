<?php

namespace App\Livewire;

use App\Models\EmployeeMonthlyStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LeaderboardWidget extends Component
{
    public string $period;

    public function mount()
    {
        $this->period = Carbon::now()->format('Y-m');
    }

    public function render()
    {
        $user = Auth::user();

        // 1. Global Leaderboard (Seluruh Karyawan) - Cached for 5 minutes
        $globalTopEmployees = \Illuminate\Support\Facades\Cache::remember('leaderboard_global_' . $this->period, 300, function () {
            if (!EmployeeMonthlyStat::where('period', $this->period)->exists()) {
                EmployeeMonthlyStat::recalculateForPeriod($this->period);
            }
            return EmployeeMonthlyStat::with(['user', 'division'])
                ->where('period', $this->period)
                ->orderByDesc('score')
                ->orderByDesc('total_early_minutes')
                ->take(5)
                ->get();
        });

        // 2. Division Leaderboard (Per Divisi) - Cached for 5 minutes
        $divisionName = $user?->division?->name;
        $userDivId = $user?->division_id;

        $divisionTopEmployees = \Illuminate\Support\Facades\Cache::remember('leaderboard_div_' . ($userDivId ?? 'all') . '_' . $this->period, 300, function () use ($userDivId) {
            $divisionQuery = EmployeeMonthlyStat::with(['user', 'division'])
                ->where('period', $this->period);

            if ($userDivId) {
                $divisionQuery->where('division_id', $userDivId);
            }

            return $divisionQuery->orderByDesc('score')
                ->orderByDesc('total_early_minutes')
                ->take(5)
                ->get();
        });

        $globalRankComments = [
            0 => 'Terlalu Menyala🔥',
            1 => 'Gokill',
            2 => 'Mantappu',
            3 => 'Well Done',
            4 => 'Okelah. Not bad',
        ];

        return view('livewire.leaderboard-widget', [
            'globalTopEmployees' => $globalTopEmployees,
            'divisionTopEmployees' => $divisionTopEmployees,
            'divisionName' => $divisionName,
            'globalRankComments' => $globalRankComments,
            'periodName' => Carbon::now()->translatedFormat('F Y'),
        ]);
    }
}
