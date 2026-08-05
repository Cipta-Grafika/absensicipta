<?php

namespace App\Livewire\Payroll;

use App\Models\EmployeeSalary;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class EmployeeSalaryComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $search = '';
    public $division = '';
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
    public $savings_id = null;

    protected $rules = [
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
        'savings_id' => 'nullable|exists:savings,id',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDivision()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['employee_id', 'salary_type', 'working_days_per_month', 'basic_salary', 'overtime_rate', 'meal_allowance', 'transport_allowance', 'attendance_allowance', 'late_deduction_rate', 'annual_leave_quota', 'savings_id']);
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function edit($employee_id)
    {
        $this->resetValidation();
        $salary = EmployeeSalary::where('employee_id', $employee_id)->first();
        
        $this->employee_id = $employee_id;

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
            $this->savings_id = $salary->savings_id;
        } else {
            $this->reset(['salary_type', 'working_days_per_month', 'basic_salary', 'overtime_rate', 'meal_allowance', 'transport_allowance', 'attendance_allowance', 'late_deduction_rate', 'annual_leave_quota', 'savings_id']);
            $this->salary_type = 'monthly';
            $this->working_days_per_month = 25;
        }

        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $this->employee_id],
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
                'savings_id' => $this->savings_id ?: null,
            ]
        );

        $this->closeModal();
        $this->banner('Data gaji karyawan berhasil disimpan.');
    }

    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);

        $employees = User::where('group', 'user')
            ->whereIn('status', ['active', 'suspend'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nip', 'like', '%' . $this->search . '%');
            })
            ->when($this->division, function ($query) {
                $query->where('division_id', $this->division);
            })
            ->with(['salary', 'division', 'jobTitle'])
            ->paginate(15);

        $savingsList = \App\Models\Saving::all();

        return view('livewire.payroll.employee-salary-component', [
            'employees' => $employees,
            'savingsList' => $savingsList,
        ])->layout('layouts.app');
    }
}
