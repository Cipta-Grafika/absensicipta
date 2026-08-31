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
        'bpjs',
        'pph21',
        'tax_master_id',
        'savings_id',
        'custom_secondary_savings',
    ];

    protected $casts = [
        'basic_salary' => 'float',
        'overtime_rate' => 'float',
        'meal_allowance' => 'float',
        'transport_allowance' => 'float',
        'attendance_allowance' => 'float',
        'late_deduction_rate' => 'float',
        'bpjs' => 'float',
        'pph21' => 'float',
        'custom_secondary_savings' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function savings()
    {
        return $this->belongsTo(Saving::class, 'savings_id');
    }

    public function taxMaster()
    {
        return $this->belongsTo(TaxMaster::class, 'tax_master_id');
    }

    /**
     * Get effective voluntary savings (custom override if present, else master saving default).
     */
    public function getEffectiveSecondarySavingsAttribute(): float
    {
        if ($this->custom_secondary_savings !== null) {
            return (float) $this->custom_secondary_savings;
        }
        return (float) ($this->savings?->secondary_savings ?? 0);
    }

    public function getLateDeductionPerMinuteAttribute(): float
    {
        return (float) ($this->late_deduction_rate ?? 0);
    }
}
