<?php

namespace App\Livewire\Admin\MasterData;

use App\Models\Division;
use App\Models\EmployeeMonthlyStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class LeaderboardComponent extends Component
{
    use WithPagination;
    use InteractsWithBanner;

    public string $period;
    public ?string $division_id = null;
    public ?string $search = null;

    public function mount()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya SuperAdmin yang dapat mengelola Leaderboard.');
        }

        $this->period = Carbon::now()->format('Y-m');
    }

    public function updatingPeriod(): void
    {
        $this->resetPage();
    }

    public function updatingDivisionId(): void
    {
        $this->resetPage();
    }

    public function syncLeaderboard()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        EmployeeMonthlyStat::recalculateForPeriod($this->period);
        $this->banner(__('Leaderboard bulanan periode ' . $this->period . ' berhasil dihitung ulang dan diperbarui.'));
    }

    public function render()
    {
        // Auto-recalculate if period has no records yet
        if (EmployeeMonthlyStat::where('period', $this->period)->count() === 0) {
            EmployeeMonthlyStat::recalculateForPeriod($this->period);
        }

        $stats = EmployeeMonthlyStat::with(['user', 'division'])
            ->where('period', $this->period)
            ->when($this->division_id, fn ($q) => $q->where('division_id', $this->division_id))
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%' . $this->search . '%')->orWhere('nip', 'like', '%' . $this->search . '%'));
            })
            ->orderBy('rank')
            ->paginate(15);

        $divisions = Division::orderBy('name')->get();

        return view('livewire.admin.master-data.leaderboard', [
            'stats' => $stats,
            'divisions' => $divisions,
        ]);
    }
}
