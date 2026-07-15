<?php

namespace App\Exports;

use App\Models\EmployeeSalary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeSalariesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return EmployeeSalary::with(['employee', 'savings'])->get();
    }

    public function headings(): array
    {
        return [
            'employee_nip',
            'salary_type',
            'working_days_per_month',
            'basic_salary',
            'overtime_rate',
            'meal_allowance',
            'transport_allowance',
            'attendance_allowance',
            'late_deduction_rate',
            'annual_leave_quota',
            'savings_name',
        ];
    }

    public function map($salary): array
    {
        return [
            $salary->employee->nip ?? '',
            $salary->salary_type,
            $salary->working_days_per_month,
            $salary->basic_salary,
            $salary->overtime_rate,
            $salary->meal_allowance,
            $salary->transport_allowance,
            $salary->attendance_allowance,
            $salary->late_deduction_rate,
            $salary->annual_leave_quota,
            $salary->savings->savings_name ?? '',
        ];
    }
}
