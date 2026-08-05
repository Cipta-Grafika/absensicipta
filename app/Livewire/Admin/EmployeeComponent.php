<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EmployeeComponent extends Component
{
    use WithPagination, InteractsWithBanner, WithFileUploads;

    public UserForm $form;
    public $deleteName = null;
    public $creating = false;
    public $editing = false;
    public $confirmingDeletion = false;
    public $selectedId = null;
    public $showDetail = null;

    # filter
    public ?string $status = null;
    public ?string $division = null;
    public ?string $jobTitle = null;
    public ?string $education = null;
    public ?string $search = null;

    public function show($id)
    {
        $this->form->setUser(User::find($id));
        $this->showDetail = true;
    }

    #[On('show-creating')]
    public function showCreating()
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->creating = true;
        $this->form->password = 'password';
    }

    public function create()
    {
        $this->form->store();
        $this->creating = false;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->editing = true;
        /** @var User $user */
        $user = User::find($id);
        $this->form->setUser($user);
    }

    public function update()
    {
        $this->form->update();
        $this->editing = false;
        $this->banner(__('Updated successfully.'));
    }

    public function deleteProfilePhoto()
    {
        $this->form->deleteProfilePhoto();
    }

    public function confirmDeletion($id, $name)
    {
        $this->deleteName = $name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete()
    {
        $user = User::find($this->selectedId);
        $this->form->setUser($user)->delete();
        $this->confirmingDeletion = false;
        $this->banner(__('Deleted successfully.'));
    }

    public function render()
    {
        $baseQuery = User::where('group', 'user')
            ->when(auth()->user()->group === 'admin', fn (Builder $q) => $q->where('division_id', auth()->user()->division_id));

        $activeSuspendCount = (clone $baseQuery)->whereIn('status', ['active', 'suspend'])->count();
        $suspendCount = (clone $baseQuery)->where('status', 'suspend')->count();
        $resignCount = (clone $baseQuery)->where('status', 'resign')->count();
        $firedCount = (clone $baseQuery)->where('status', 'fired')->count();

        $usersQuery = clone $baseQuery;

        if (empty($this->status)) {
            // Default filter: only show working employees (active & suspend)
            $usersQuery->whereIn('status', ['active', 'suspend']);
        } else {
            $usersQuery->where('status', $this->status);
        }

        $users = $usersQuery
            ->when($this->search, function (Builder $q) {
                return $q->where(function (Builder $query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('nip', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->division, function (Builder $q) {
                if (auth()->user()->group === 'admin' && $this->division != auth()->user()->division_id) {
                    return $q->whereRaw('1 = 0');
                }
                return $q->where('division_id', $this->division);
            })
            ->when($this->jobTitle, fn (Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->when($this->education, fn (Builder $q) => $q->where('education_id', $this->education))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.employees', [
            'users' => $users,
            'activeSuspendCount' => $activeSuspendCount,
            'suspendCount' => $suspendCount,
            'resignCount' => $resignCount,
            'firedCount' => $firedCount,
        ]);
    }
}
