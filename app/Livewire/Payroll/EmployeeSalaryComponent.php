<?php

namespace App\Livewire\Payroll;

use App\Models\EmployeeSalary;
use App\Models\User;
use App\Models\Division;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class EmployeeSalaryComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $search = '';
    public $division = '';
    public $status = '';
    public $isModalOpen = false;

    // Form fields
    public $employee_id;
    public $salary_type = 'monthly';
    public $working_days_per_month = 25;
    public $basic_salary = 0;
    public $overtime_rate = 0;
    public $meal_allowance = 0;
    public $transport_allowance = 0;
    public $attendance_allowance = 0;
    public $late_deduction_rate = 0;
    public $annual_leave_quota = 12;
    public $bpjs = 0;
    public $pph21 = 0;
    public $tax_master_id = null;
    public $savings_id = null;
    public $custom_secondary_savings = null;

    protected function rules()
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'salary_type' => 'required|in:monthly,daily',
            'working_days_per_month' => 'required_if:salary_type,monthly|numeric|min:1|max:31',
            'basic_salary' => 'required|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'attendance_allowance' => 'nullable|numeric|min:0',
            'late_deduction_rate' => 'nullable|numeric|min:0',
            'annual_leave_quota' => 'required|numeric|min:0',
            'bpjs' => 'nullable|numeric|min:0',
            'pph21' => 'nullable|numeric|min:0',
            'tax_master_id' => 'nullable|exists:tax_masters,id',
            'savings_id' => 'nullable|exists:savings,id',
            'custom_secondary_savings' => 'nullable|numeric|min:0',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDivision()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['employee_id', 'salary_type', 'working_days_per_month', 'basic_salary', 'overtime_rate', 'meal_allowance', 'transport_allowance', 'attendance_allowance', 'late_deduction_rate', 'annual_leave_quota', 'bpjs', 'pph21', 'tax_master_id', 'savings_id', 'custom_secondary_savings']);
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function edit($employee_id)
    {
        $this->resetValidation();
        $employee = User::onlyEmployee()->findOrFail($employee_id);
        $salary = EmployeeSalary::where('employee_id', $employee->id)->first();
        
        $this->employee_id = $employee->id;

        if ($salary) {
            $this->salary_type = $salary->salary_type;
            $this->working_days_per_month = $salary->working_days_per_month;
            $this->basic_salary = $salary->basic_salary;
            $this->overtime_rate = $salary->overtime_rate;
            $this->meal_allowance = $salary->meal_allowance;
            $this->transport_allowance = $salary->transport_allowance;
            $this->attendance_allowance = $salary->attendance_allowance;
            $this->late_deduction_rate = $salary->late_deduction_rate;
            $this->annual_leave_quota = $salary->annual_leave_quota ?? 12;
            $this->bpjs = $salary->bpjs ?? 0;
            $this->pph21 = $salary->pph21 ?? 0;
            $this->tax_master_id = $salary->tax_master_id;
            $this->savings_id = $salary->savings_id;
            $this->custom_secondary_savings = $salary->custom_secondary_savings;
        } else {
            $this->reset(['salary_type', 'working_days_per_month', 'basic_salary', 'overtime_rate', 'meal_allowance', 'transport_allowance', 'attendance_allowance', 'late_deduction_rate', 'annual_leave_quota', 'bpjs', 'pph21', 'tax_master_id', 'savings_id', 'custom_secondary_savings']);
            $isPureDailyType = in_array(strtolower($employee->type ?? ''), ['intern', 'part-time', 'pkl', 'magang', 'freelance', 'volunteer']);
            $this->salary_type = $isPureDailyType ? 'daily' : 'monthly';
            $this->working_days_per_month = 25;
        }

        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        $employee = User::onlyEmployee()->findOrFail($this->employee_id);

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'salary_type' => $this->salary_type,
                'working_days_per_month' => $this->salary_type == 'monthly' ? $this->working_days_per_month : 25,
                'basic_salary' => $this->basic_salary,
                'overtime_rate' => $this->overtime_rate ?? 0,
                'meal_allowance' => $this->meal_allowance ?? 0,
                'transport_allowance' => $this->transport_allowance ?? 0,
                'attendance_allowance' => $this->attendance_allowance ?? 0,
                'late_deduction_rate' => $this->late_deduction_rate ?? 0,
                'annual_leave_quota' => $this->annual_leave_quota ?? 12,
                'bpjs' => ($this->bpjs !== '' && $this->bpjs !== null) ? (float) $this->bpjs : 0,
                'pph21' => ($this->pph21 !== '' && $this->pph21 !== null) ? (float) $this->pph21 : 0,
                'tax_master_id' => $this->tax_master_id ?: null,
                'savings_id' => $this->savings_id ?: null,
                'custom_secondary_savings' => ($this->custom_secondary_savings !== '' && $this->custom_secondary_savings !== null) ? $this->custom_secondary_savings : null,
            ]
        );

        $this->closeModal();
        $this->banner('Data gaji karyawan berhasil disimpan.');
    }

    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);

        $employees = User::onlyEmployee()
            ->when($this->status, function ($query) {
                if ($this->status === 'all') {
                    return $query;
                }
                return $query->where('status', $this->status);
            }, function ($query) {
                // By default display only active and suspend working employees
                return $query->whereIn('status', ['active', 'suspend']);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nip', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->division, function ($query) {
                $query->where('division_id', $this->division);
            })
            ->with(['salary.savings', 'salary.taxMaster', 'division', 'jobTitle'])
            ->orderBy('name')
            ->paginate(15);

        $divisions = Division::all();
        $savingsList = \App\Models\Saving::orderBy('savings_name')->get();
        $taxMastersList = \App\Models\TaxMaster::orderBy('min_gross_income', 'asc')->get();

        return view('livewire.payroll.employee-salary-component', [
            'employees' => $employees,
            'divisions' => $divisions,
            'savingsList' => $savingsList,
            'taxMastersList' => $taxMastersList,
        ])->layout('layouts.app');
    }
}
