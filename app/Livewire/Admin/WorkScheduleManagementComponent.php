<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class WorkScheduleManagementComponent extends Component
{
    use WithPagination;
    use InteractsWithBanner;

    // Form inputs
    public array $user_ids = [];
    public ?string $start_date = null;
    public ?string $end_date = null;
    public int|bool $is_working_day = 1;
    public ?string $note = null;

    // Modal state
    public bool $creating = false;
    public bool $confirmingDeletion = false;
    public ?int $selectedId = null;

    // Filters
    public ?string $search = null;
    public ?string $filter_division_id = null;
    public ?string $filter_user_id = null;
    public ?string $filter_start_date = null;
    public ?string $filter_end_date = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_working_day' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[On('show-creating')]
    public function showCreating()
    {
        $this->resetErrorBag();
        $this->reset(['user_ids', 'start_date', 'end_date', 'note', 'selectedId']);
        $this->is_working_day = 1;
        $this->creating = true;
    }

    public function create()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        // Division Scope Security Check for Admin
        if (!$user->isSuperadmin) {
            $allowedUserIds = User::where('division_id', $user->division_id)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $filteredUserIds = array_filter($this->user_ids, fn($id) => in_array((string)$id, $allowedUserIds));

            if (empty($filteredUserIds)) {
                $this->addError('user_ids', 'Karyawan yang dipilih tidak sesuai dengan divisi Anda.');
                return;
            }
            $this->user_ids = array_values($filteredUserIds);
        }

        $this->validate();

        $endDate = $this->end_date ? Carbon::parse($this->end_date) : Carbon::parse($this->start_date);
        $startDate = Carbon::parse($this->start_date);

        DB::transaction(function () use ($startDate, $endDate) {
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $dateStr = $current->format('Y-m-d');
                foreach ($this->user_ids as $userId) {
                    WorkSchedule::updateOrCreate(
                        [
                            'date' => $dateStr,
                            'user_id' => $userId,
                        ],
                        [
                            'is_working_day' => $this->is_working_day,
                            'note' => $this->note,
                            'created_by' => Auth::id(),
                        ]
                    );
                }
                $current->addDay();
            }
        });

        $this->creating = false;
        $this->banner(__('Roster work schedule saved successfully.'));
    }

    public function confirmDeletion(int $id)
    {
        $user = Auth::user();
        $sched = WorkSchedule::with('user')->findOrFail($id);

        if (!$user->isSuperadmin && $sched->user?->division_id !== $user->division_id) {
            return abort(403, 'Akses Ditolak: Anda hanya berhak menghapus jadwal karyawan divisi Anda.');
        }

        $this->selectedId = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        if ($this->selectedId) {
            $sched = WorkSchedule::with('user')->find($this->selectedId);
            if ($sched) {
                if (!$user->isSuperadmin && $sched->user?->division_id !== $user->division_id) {
                    return abort(403, 'Akses Ditolak: Anda hanya berhak menghapus jadwal karyawan divisi Anda.');
                }
                $sched->delete();
                $this->banner(__('Schedule entry deleted successfully.'));
            }
        }

        $this->confirmingDeletion = false;
        $this->selectedId = null;
    }

    public function render()
    {
        $user = Auth::user();
        $query = WorkSchedule::with(['user.division', 'createdBy']);

        // Non-superadmin is restricted to their own division's schedules
        if (!$user->isSuperadmin) {
            $query->whereHas('user', fn ($u) => $u->where('division_id', $user->division_id));
        }

        $query->when($this->search, function ($q) {
            $q->where(function ($sub) {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', '%' . $this->search . '%')->orWhere('nip', 'like', '%' . $this->search . '%'))
                    ->orWhere('note', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->filter_division_id, function ($q) {
            $q->whereHas('user', fn ($u) => $u->where('division_id', $this->filter_division_id));
        })
        ->when($this->filter_user_id, fn ($q) => $q->where('user_id', $this->filter_user_id))
        ->when($this->filter_start_date, fn ($q) => $q->where('date', '>=', $this->filter_start_date))
        ->when($this->filter_end_date, fn ($q) => $q->where('date', '<=', $this->filter_end_date))
        ->orderBy('date', 'desc');

        $schedules = $query->paginate(20);

        $divisions = $user->isSuperadmin ? Division::orderBy('name')->get() : collect();

        // Scope employee options for modal & filters based on user role
        $usersQuery = User::where('group', 'user')->where('status', 'active');
        if (!$user->isSuperadmin) {
            $usersQuery->where('division_id', $user->division_id);
        }
        $users = $usersQuery->orderBy('name')->get();

        return view('livewire.admin.work-schedule-management', [
            'schedules' => $schedules,
            'divisions' => $divisions,
            'users' => $users,
        ])->layout('layouts.app');
    }
}
