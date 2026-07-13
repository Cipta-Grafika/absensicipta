<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'employee_id',
        'salary_type',
        'working_days_per_month',
        'basic_salary',
        'overtime_rate',
        'meal_allowance',
        'transport_allowance',
        'attendance_allowance',
        'late_deduction_rate',
        'annual_leave_quota',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
