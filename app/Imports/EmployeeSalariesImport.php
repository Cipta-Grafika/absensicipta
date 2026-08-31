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
            $user = User::onlyWorkingEmployee()->where('nip', $row['employee_nip'])->first();
        }

        if (!$user && !empty($row['employee_name'])) {
            $user = User::onlyWorkingEmployee()->whereRaw('LOWER(name) = ?', [strtolower($row['employee_name'])])->first();
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

        $customSecondarySavings = null;
        if (isset($row['custom_secondary_savings']) && is_numeric($row['custom_secondary_savings'])) {
            $customSecondarySavings = (float) $row['custom_secondary_savings'];
        }

        $bpjs = 0;
        if (isset($row['bpjs']) && is_numeric($row['bpjs'])) {
            $bpjs = (float) $row['bpjs'];
        }

        $pph21 = 0;
        if (isset($row['pph21']) && is_numeric($row['pph21'])) {
            $pph21 = (float) $row['pph21'];
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
            'bpjs' => $bpjs,
            'pph21' => $pph21,
            'savings_id' => $savingsId,
            'custom_secondary_savings' => $customSecondarySavings,
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
            'employee_nip' => ['nullable'],
            'employee_name' => ['nullable'],
            'salary_type' => ['required'],
            'working_days_per_month' => ['required', 'numeric'],
            'basic_salary' => ['required', 'numeric'],
            'overtime_rate' => ['required', 'numeric'],
            'meal_allowance' => ['required', 'numeric'],
            'transport_allowance' => ['required', 'numeric'],
            'attendance_allowance' => ['required', 'numeric'],
            'late_deduction_rate' => ['required', 'numeric'],
            'annual_leave_quota' => ['required', 'numeric'],
            'bpjs' => ['nullable', 'numeric', 'min:0'],
            'pph21' => ['nullable', 'numeric', 'min:0'],
            'savings_name' => ['nullable', 'exists:savings,savings_name'],
            'custom_secondary_savings' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        $messages = [];
        foreach ($failures as $failure) {
            $messages[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
        }
        throw new \Exception(implode('<br>', $messages));
    }
}
