<?php

namespace App\Livewire\Admin\MasterData;

use App\Livewire\Forms\OvertimeRateForm;
use App\Models\Division;
use App\Models\OvertimeRate;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;

class OvertimeRateComponent extends Component
{
    use InteractsWithBanner;

    public OvertimeRateForm $form;
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
        $this->banner(__('Tarif lembur berhasil dibuat.'));
    }

    public function edit($id)
    {
        $user = Auth::user();
        /** @var OvertimeRate $rate */
        $rate = OvertimeRate::findOrFail($id);

        if (!$user->isSuperadmin && $rate->division_id !== $user->division_id) {
            return abort(403);
        }

        $this->form->resetErrorBag();
        $this->editing = true;
        $this->form->setRate($rate);
    }

    public function update()
    {
        $this->form->update();
        $this->editing = false;
        $this->banner(__('Tarif lembur berhasil diperbarui.'));
    }

    public function confirmDeletion($id, $name)
    {
        $user = Auth::user();
        /** @var OvertimeRate $rate */
        $rate = OvertimeRate::findOrFail($id);

        if (!$user->isSuperadmin && $rate->division_id !== $user->division_id) {
            return abort(403);
        }

        $this->deleteName = $name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete()
    {
        $rate = OvertimeRate::findOrFail($this->selectedId);
        $this->form->setRate($rate)->delete();
        $this->confirmingDeletion = false;
        $this->banner(__('Tarif lembur berhasil dihapus.'));
    }

    public function render()
    {
        $user = Auth::user();
        $query = OvertimeRate::with('division');

        if ($user->isSuperadmin) {
            $rates = $query->orderBy('min_hours', 'asc')->get();
        } else {
            $rates = $query->where('division_id', $user->division_id)->orderBy('min_hours', 'asc')->get();
        }

        $divisions = $user->isSuperadmin ? Division::orderBy('name')->get() : collect();

        return view('livewire.admin.master-data.overtime-rate', [
            'rates' => $rates,
            'divisions' => $divisions,
        ]);
    }
}
