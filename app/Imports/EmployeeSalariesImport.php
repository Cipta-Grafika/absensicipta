<?php

namespace App\Imports;

use App\Models\EmployeeSalary;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class EmployeeSalariesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public function __construct(public bool $save = true)
    {
    }

    public function model(array $row)
    {
        $user = null;

        if (!empty($row['employee_nip'])) {
            $user = User::where('nip', $row['employee_nip'])->first();
        }

        if (!$user && !empty($row['employee_name'])) {
            $user = User::whereRaw('LOWER(name) = ?', [strtolower($row['employee_name'])])->first();
        }

        if (!$user) {
            return null; // Skip if user not found
        }

        $salary = EmployeeSalary::firstOrNew(['employee_id' => $user->id]);

        $savingsId = null;
        if (!empty($row['savings_name'])) {
            $saving = \App\Models\Saving::where('savings_name', $row['savings_name'])->first();
            if ($saving) {
                $savingsId = $saving->id;
            }
        }

        $salary->forceFill([
            'salary_type' => $row['salary_type'],
            'working_days_per_month' => $row['working_days_per_month'],
            'basic_salary' => $row['basic_salary'],
            'overtime_rate' => $row['overtime_rate'],
            'meal_allowance' => $row['meal_allowance'],
            'transport_allowance' => $row['transport_allowance'],
            'attendance_allowance' => $row['attendance_allowance'],
            'late_deduction_rate' => $row['late_deduction_rate'],
            'annual_leave_quota' => $row['annual_leave_quota'],
            'savings_id' => $savingsId,
        ]);

        if ($this->save) {
            $salary->save();
        }

        // For preview purposes, attach the user manually so it displays correctly
        $salary->setRelation('employee', $user);
        if (isset($saving)) {
            $salary->setRelation('savings', $saving);
        }

        return $salary;
    }

    public function rules(): array
    {
        return [
            'employee_nip' => ['nullable', 'string'],
            'employee_name' => ['nullable', 'string'],
            'salary_type' => ['required'],
            'working_days_per_month' => ['required', 'numeric'],
            'basic_salary' => ['required', 'numeric'],
            'overtime_rate' => ['required', 'numeric'],
            'meal_allowance' => ['required', 'numeric'],
            'transport_allowance' => ['required', 'numeric'],
            'attendance_allowance' => ['required', 'numeric'],
            'late_deduction_rate' => ['required', 'numeric'],
            'annual_leave_quota' => ['required', 'numeric'],
            'savings_name' => ['nullable', 'exists:savings,savings_name'],
        ];
    }

    public function onFailure(Failure ...$failures)
    {
    }
}
