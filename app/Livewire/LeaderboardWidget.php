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

        // Ensure current month stats are computed if empty
        if (!EmployeeMonthlyStat::where('period', $this->period)->exists()) {
            EmployeeMonthlyStat::recalculateForPeriod($this->period);
        }

        // 1. Global Leaderboard (Seluruh Karyawan)
        $globalTopEmployees = EmployeeMonthlyStat::with(['user', 'division'])
            ->where('period', $this->period)
            ->orderByDesc('score')
            ->orderByDesc('total_early_minutes')
            ->take(5)
            ->get();

        // 2. Division Leaderboard (Per Divisi)
        $divisionQuery = EmployeeMonthlyStat::with(['user', 'division'])
            ->where('period', $this->period);

        $divisionName = null;
        if ($user && $user->division_id) {
            $divisionQuery->where('division_id', $user->division_id);
            $divisionName = $user->division?->name;
        }

        $divisionTopEmployees = $divisionQuery->orderByDesc('score')
            ->orderByDesc('total_early_minutes')
            ->take(5)
            ->get();

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
