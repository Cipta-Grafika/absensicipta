<?php

namespace App\Livewire\Payroll;

use App\Models\EmployeeSalary;
use App\Models\Saving;
use App\Models\Division;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class SavingComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $activeTab = 'master'; // 'master' | 'members'
    public $search = '';

    // Member tab filters
    public $memberSearch = '';
    public $memberDivision = '';
    public $overrideFilter = ''; // '' | 'custom' | 'default'

    // Form fields for Master Saving
    public $isModalOpen = false;
    public $isConfirmingDeletion = false;
    public $saving_id;
    public $savings_name;
    public $mandatory_savings = 0;
    public $secondary_savings = 0;

    // Form fields for Custom Sukarela Override Modal
    public $isCustomSukarelaModalOpen = false;
    public $selectedSalaryId = null;
    public $selectedEmployeeName = '';
    public $selectedEmployeeNip = '';
    public $selectedSavingName = '';
    public $selectedMasterSukarela = 0;
    public $customSukarelaMode = 'default'; // 'default' | 'custom'
    public $customSukarelaNominal = 0;

    protected $queryString = [
        'activeTab' => ['except' => 'master'],
        'memberSearch' => ['except' => ''],
        'memberDivision' => ['except' => ''],
        'overrideFilter' => ['except' => ''],
    ];

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

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMemberSearch()
    {
        $this->resetPage();
    }

    public function updatingMemberDivision()
    {
        $this->resetPage();
    }

    public function updatingOverrideFilter()
    {
        $this->resetPage();
    }

    // --- Master Saving Actions ---

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

    // --- Custom Sukarela Override Actions ---

    public function openCustomSukarelaModal($salaryId)
    {
        $salary = EmployeeSalary::with(['employee.division', 'savings'])->findOrFail($salaryId);
        
        $this->selectedSalaryId = $salary->id;
        $this->selectedEmployeeName = $salary->employee->name ?? '-';
        $this->selectedEmployeeNip = $salary->employee->nip ?? '-';
        $this->selectedSavingName = $salary->savings->savings_name ?? 'Syirkah';
        $this->selectedMasterSukarela = (float) ($salary->savings->secondary_savings ?? 0);

        if ($salary->custom_secondary_savings !== null) {
            $this->customSukarelaMode = 'custom';
            $this->customSukarelaNominal = (float) $salary->custom_secondary_savings;
        } else {
            $this->customSukarelaMode = 'default';
            $this->customSukarelaNominal = (float) $this->selectedMasterSukarela;
        }

        $this->isCustomSukarelaModalOpen = true;
    }

    public function closeCustomSukarelaModal()
    {
        $this->isCustomSukarelaModalOpen = false;
        $this->selectedSalaryId = null;
    }

    public function saveCustomSukarela()
    {
        if (!$this->selectedSalaryId) return;

        $salary = EmployeeSalary::findOrFail($this->selectedSalaryId);

        if ($this->customSukarelaMode === 'custom') {
            $salary->update([
                'custom_secondary_savings' => max(0, (float) $this->customSukarelaNominal),
            ]);
            $this->banner("Nominal syirkah sukarela untuk {$this->selectedEmployeeName} berhasil di-custom menjadi Rp " . number_format($this->customSukarelaNominal, 0, ',', '.') . ".");
        } else {
            $salary->update([
                'custom_secondary_savings' => null,
            ]);
            $this->banner("Nominal syirkah sukarela untuk {$this->selectedEmployeeName} dikembalikan mengikuti master program default (Rp " . number_format($this->selectedMasterSukarela, 0, ',', '.') . ").");
        }

        $this->closeCustomSukarelaModal();
    }

    public function resetCustomSukarelaToDefault($salaryId)
    {
        $salary = EmployeeSalary::with('employee')->findOrFail($salaryId);
        $salary->update([
            'custom_secondary_savings' => null,
        ]);

        $empName = $salary->employee->name ?? 'Karyawan';
        $this->banner("Nominal syirkah sukarela {$empName} berhasil di-reset mengikuti default master program.");
    }

    public function render()
    {
        $savings = Saving::where('savings_name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10, ['*'], 'savings_page');

        // Member salaries with Syirkah assigned
        $membersQuery = EmployeeSalary::with(['employee.division', 'savings'])
            ->whereNotNull('savings_id')
            ->whereHas('employee', function ($q) {
                $q->where('group', 'user')
                  ->whereIn('status', ['active', 'suspend']);
                if ($this->memberSearch) {
                    $q->where(function ($sub) {
                        $sub->where('name', 'like', '%' . $this->memberSearch . '%')
                            ->orWhere('nip', 'like', '%' . $this->memberSearch . '%');
                    });
                }
                if ($this->memberDivision) {
                    $q->where('division_id', $this->memberDivision);
                }
            });

        if ($this->overrideFilter === 'custom') {
            $membersQuery->whereNotNull('custom_secondary_savings');
        } elseif ($this->overrideFilter === 'default') {
            $membersQuery->whereNull('custom_secondary_savings');
        }

        $memberSalaries = $membersQuery->paginate(15, ['*'], 'members_page');
        $divisions = Division::orderBy('name')->get();

        $totalMembers = EmployeeSalary::whereNotNull('savings_id')->count();
        $customMembersCount = EmployeeSalary::whereNotNull('savings_id')->whereNotNull('custom_secondary_savings')->count();

        return view('livewire.payroll.saving-component', [
            'savings' => $savings,
            'memberSalaries' => $memberSalaries,
            'divisions' => $divisions,
            'totalMembers' => $totalMembers,
            'customMembersCount' => $customMembersCount,
        ])->layout('layouts.app');
    }
}

