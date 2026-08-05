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

        $query = EmployeeMonthlyStat::with(['user', 'division'])
            ->where('period', $this->period);

        // Scope to user's division for regular users / admin
        if ($user && !$user->isSuperadmin && $user->division_id) {
            $divisionQuery = (clone $query)->where('division_id', $user->division_id);
            if ($divisionQuery->exists()) {
                $query = $divisionQuery;
            }
        }

        $topEmployees = $query->orderByDesc('score')
            ->orderByDesc('total_early_minutes')
            ->take(5)
            ->get();

        return view('livewire.leaderboard-widget', [
            'topEmployees' => $topEmployees,
            'periodName' => Carbon::now()->translatedFormat('F Y'),
        ]);
    }
}
