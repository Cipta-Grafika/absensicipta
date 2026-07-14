<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'employee_id',
        'period_month',
        'start_date',
        'end_date',
        'total_present',
        'total_wfh',
        'total_absent',
        'total_sick',
        'total_excused',
        'penalized_cuti_days',
        'late_days_count',
        'total_late_minutes',
        'total_overtime_hours',
        'total_unreplaced_imp_hours',
        'basic_salary_earned',
        'total_allowance',
        'total_overtime_pay',
        'total_deduction',
        'net_salary',
        'status',
        'payment_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function details()
    {
        return $this->hasMany(PayrollDetail::class);
    }
}
