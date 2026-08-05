<?php

namespace App\Livewire\Admin\MasterData;

use App\Livewire\Forms\ShiftForm;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;

class ShiftComponent extends Component
{
    use InteractsWithBanner;

    public ShiftForm $form;
    public $deleteName = null;
    public $creating = false;
    public $editing = false;
    public $confirmingDeletion = false;
    public $selectedId = null;

    #[On('show-creating')]
    public function showCreating()
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        
        $user = Auth::user();
        if (!$user->isSuperadmin) {
            $this->form->division_id = $user->division_id;
        }

        $this->creating = true;
    }

    public function create()
    {
        $this->form->store();
        $this->creating = false;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $user = Auth::user();
        /** @var Shift $shift */
        $shift = Shift::findOrFail($id);

        if (!$user->isSuperadmin && $shift->division_id !== $user->division_id) {
            return abort(403);
        }

        $this->form->resetErrorBag();
        $this->editing = true;
        $this->form->setShift($shift);
    }

    public function update()
    {
        $this->form->update();
        $this->editing = false;
        $this->banner(__('Updated successfully.'));
    }

    public function confirmDeletion($id, $name)
    {
        $user = Auth::user();
        /** @var Shift $shift */
        $shift = Shift::findOrFail($id);

        if (!$user->isSuperadmin && $shift->division_id !== $user->division_id) {
            return abort(403);
        }

        $this->deleteName = $name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete()
    {
        $shift = Shift::findOrFail($this->selectedId);
        $this->form->setShift($shift)->delete();
        $this->confirmingDeletion = false;
        $this->banner(__('Deleted successfully.'));
    }

    public function render()
    {
        $user = Auth::user();
        $query = Shift::with('division');

        if ($user->isSuperadmin) {
            $shifts = $query->get();
        } else {
            $shifts = $query->where('division_id', $user->division_id)->get();
        }

        $divisions = $user->isSuperadmin ? \App\Models\Division::orderBy('name')->get() : collect();

        return view('livewire.admin.master-data.shift', [
            'shifts' => $shifts,
            'divisions' => $divisions,
        ]);
    }
}
