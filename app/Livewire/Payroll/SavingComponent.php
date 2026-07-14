<?php

namespace App\Livewire\Payroll;

use App\Models\Saving;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class SavingComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $search = '';
    public $isModalOpen = false;
    public $isConfirmingDeletion = false;

    // Form fields
    public $saving_id;
    public $savings_name;
    public $mandatory_savings = 0;
    public $secondary_savings = 0;

    protected function rules()
    {
        return [
            'savings_name' => 'required|string|max:255',
            'mandatory_savings' => 'required|numeric|min:0',
            'secondary_savings' => 'required|numeric|min:0',
        ];
    }

    protected function messages()
    {
        return [
            'savings_name.required' => 'Nama Syirkah wajib diisi.',
            'mandatory_savings.required' => 'Nominal Wajib wajib diisi.',
            'mandatory_savings.numeric' => 'Nominal Wajib harus berupa angka.',
            'secondary_savings.required' => 'Nominal Sukarela wajib diisi.',
            'secondary_savings.numeric' => 'Nominal Sukarela harus berupa angka.',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['saving_id', 'savings_name', 'mandatory_savings', 'secondary_savings']);
        $this->mandatory_savings = 0;
        $this->secondary_savings = 0;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->isConfirmingDeletion = false;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $saving = Saving::findOrFail($id);
        
        $this->saving_id = $saving->id;
        $this->savings_name = $saving->savings_name;
        $this->mandatory_savings = $saving->mandatory_savings;
        $this->secondary_savings = $saving->secondary_savings;

        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        Saving::updateOrCreate(
            ['id' => $this->saving_id],
            [
                'savings_name' => $this->savings_name,
                'mandatory_savings' => $this->mandatory_savings,
                'secondary_savings' => $this->secondary_savings,
            ]
        );

        $this->banner($this->saving_id ? 'Data Syirkah berhasil diperbarui.' : 'Data Syirkah berhasil ditambahkan.');
        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        $this->saving_id = $id;
        $this->isConfirmingDeletion = true;
    }

    public function cancelDelete()
    {
        $this->isConfirmingDeletion = false;
        $this->reset(['saving_id']);
    }

    public function delete()
    {
        if ($this->saving_id) {
            Saving::findOrFail($this->saving_id)->delete();
            $this->banner('Data Syirkah berhasil dihapus.');
        }

        $this->closeModal();
    }

    public function render()
    {
        $savings = Saving::where('savings_name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.payroll.saving-component', [
            'savings' => $savings
        ])->layout('layouts.app');
    }
}
