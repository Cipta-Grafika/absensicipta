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

    // Form inputs (Create)
    public array $user_ids = [];
    public ?string $start_date = null;
    public ?string $end_date = null;
    public int|bool $is_working_day = 1;
    public ?string $note = null;

    // Form inputs (Edit)
    public ?int $editing_id = null;
    public ?string $edit_user_id = null;
    public ?string $edit_date = null;
    public int|bool $edit_is_working_day = 1;
    public ?string $edit_note = null;

    // Modal state
    public bool $creating = false;
    public bool $editing = false;
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
        $this->banner(__('Jadwal kerja rolling berhasil disimpan.'));
    }

    public function edit(int $id)
    {
        $user = Auth::user();
        $sched = WorkSchedule::with('user')->findOrFail($id);

        // Authorization Check
        if (!$user->isSuperadmin && $sched->user?->division_id !== $user->division_id) {
            return abort(403, 'Akses Ditolak: Anda hanya berhak mengubah jadwal karyawan divisi Anda.');
        }

        $this->resetErrorBag();
        $this->editing_id = $sched->id;
        $this->edit_user_id = (string) $sched->user_id;
        $this->edit_date = $sched->date?->format('Y-m-d');
        $this->edit_is_working_day = $sched->is_working_day ? 1 : 0;
        $this->edit_note = $sched->note;

        $this->editing = true;
    }

    public function update()
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        $sched = WorkSchedule::with('user')->findOrFail($this->editing_id);
        if (!$user->isSuperadmin && $sched->user?->division_id !== $user->division_id) {
            return abort(403, 'Akses Ditolak: Anda hanya berhak mengubah jadwal karyawan divisi Anda.');
        }

        $this->validate([
            'edit_user_id' => ['required', 'exists:users,id'],
            'edit_date' => ['required', 'date'],
            'edit_is_working_day' => ['required', 'boolean'],
            'edit_note' => ['nullable', 'string', 'max:255'],
        ]);

        $targetUser = User::findOrFail($this->edit_user_id);
        if (!$user->isSuperadmin && $targetUser->division_id !== $user->division_id) {
            $this->addError('edit_user_id', 'Karyawan yang dipilih tidak berada di divisi Anda.');
            return;
        }

        $sched->update([
            'user_id' => $this->edit_user_id,
            'date' => $this->edit_date,
            'is_working_day' => $this->edit_is_working_day,
            'note' => $this->edit_note,
        ]);

        $this->editing = false;
        $this->editing_id = null;
        $this->banner(__('Jadwal kerja berhasil diperbarui.'));
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
                $this->banner(__('Jadwal kerja berhasil dihapus.'));
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
        $usersQuery = User::where('group', 'user')->whereIn('status', ['active', 'suspend']);
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
