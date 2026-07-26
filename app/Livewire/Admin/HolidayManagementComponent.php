<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class HolidayManagementComponent extends Component
{
    use WithPagination;
    use InteractsWithBanner;

    public ?string $name = null;
    public ?string $date = null;
    public string $type = 'general';
    public ?int $division_id = null;
    public array $user_ids = [];
    public ?string $description = null;

    public bool $creating = false;
    public bool $editing = false;
    public bool $confirmingDeletion = false;
    public ?int $selectedId = null;

    // Filters
    public ?string $search = null;
    public ?string $filter_type = null;
    public ?string $filter_year = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:general,division,custom'],
            'division_id' => ['nullable', 'required_if:type,division', 'exists:divisions,id'],
            'user_ids' => ['nullable', 'required_if:type,custom', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    #[On('show-creating')]
    public function showCreating()
    {
        $this->resetErrorBag();
        $this->reset(['name', 'date', 'division_id', 'user_ids', 'description', 'selectedId']);
        $this->type = 'general';
        $this->creating = true;
    }

    public function create()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $this->validate();

        DB::transaction(function () {
            $holiday = Holiday::create([
                'name' => $this->name,
                'date' => $this->date,
                'type' => $this->type,
                'division_id' => $this->type === 'division' ? $this->division_id : null,
                'description' => $this->description,
                'created_by' => Auth::id(),
            ]);

            if ($this->type === 'custom' && !empty($this->user_ids)) {
                $holiday->users()->sync($this->user_ids);
            }
        });

        $this->creating = false;
        $this->banner(__('Holiday created successfully.'));
    }

    public function edit(int $id)
    {
        $this->resetErrorBag();
        $holiday = Holiday::with('users')->findOrFail($id);
        $this->selectedId = $id;
        $this->name = $holiday->name;
        $this->date = $holiday->date->format('Y-m-d');
        $this->type = $holiday->type;
        $this->division_id = $holiday->division_id;
        $this->user_ids = $holiday->users->pluck('id')->toArray();
        $this->description = $holiday->description;
        $this->editing = true;
    }

    public function update()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $this->validate();

        DB::transaction(function () {
            $holiday = Holiday::findOrFail($this->selectedId);
            $holiday->update([
                'name' => $this->name,
                'date' => $this->date,
                'type' => $this->type,
                'division_id' => $this->type === 'division' ? $this->division_id : null,
                'description' => $this->description,
            ]);

            if ($this->type === 'custom') {
                $holiday->users()->sync($this->user_ids);
            } else {
                $holiday->users()->detach();
            }
        });

        $this->editing = false;
        $this->selectedId = null;
        $this->banner(__('Holiday updated successfully.'));
    }

    public function confirmDeletion(int $id)
    {
        $this->selectedId = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        if ($this->selectedId) {
            Holiday::where('id', $this->selectedId)->delete();
            $this->banner(__('Holiday deleted successfully.'));
        }

        $this->confirmingDeletion = false;
        $this->selectedId = null;
    }

    public function render()
    {
        $query = Holiday::with(['division', 'users', 'createdBy'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('division', fn ($d) => $d->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('users', fn ($u) => $u->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->filter_type, fn ($q) => $q->where('type', $this->filter_type))
            ->when($this->filter_year, fn ($q) => $q->whereYear('date', $this->filter_year))
            ->orderBy('date', 'desc');

        $holidays = $query->paginate(20);

        $divisions = Division::orderBy('name')->get();
        $users = User::where('group', 'user')->where('status', 'active')->orderBy('name')->get();

        return view('livewire.admin.holiday-management', [
            'holidays' => $holidays,
            'divisions' => $divisions,
            'users' => $users,
        ])->layout('layouts.app');
    }
}
