<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'overtime_date',
        'start_time',
        'end_time',
        'duration_hours',
        'applied_rate_amount',
        'total_pay',
        'reason',
        'status',
        'approved_by',
        'approval_date',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'approval_date' => 'datetime',
        'duration_hours' => 'float',
        'applied_rate_amount' => 'float',
        'total_pay' => 'float',
    ];

    /**
     * Get the user that requested the overtime.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user that approved the overtime.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Accessor for overtime_pay attribute
     */
    public function getOvertimePayAttribute()
    {
        return $this->total_pay ?? 0;
    }

    /**
     * Accessor for formatted duration (e.g. 3 jam 30 menit)
     */
    public function getFormattedDurationAttribute()
    {
        $hours = $this->duration_hours ?? $this->calculateDuration();
        if ($hours <= 0) return '0 jam';
        
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        
        if ($h > 0 && $m > 0) {
            return "{$h} jam {$m} menit";
        } elseif ($h > 0) {
            return "{$h} jam";
        }
        return "{$m} menit";
    }

    /**
     * Calculate estimated pay for overtime request
     */
    public function calculateEstimatedPay()
    {
        if ($this->total_pay && $this->total_pay > 0) {
            return $this->total_pay;
        }

        $user = $this->employee ?: User::find($this->employee_id);
        $hours = $this->duration_hours ?? $this->calculateDuration();
        if ($hours <= 0) return 0;

        if (!$user || !$user->salary) {
            // Default rate calculation from OvertimeRate table if salary not set
            $defaultRate = OvertimeRate::first()?->rate_amount ?? 20000;
            return round($hours * $defaultRate, 0);
        }

        $salary = $user->salary;
        
        // Hourly rate estimate: (Basic Salary + Allowances) / (working_days * 8)
        $workingDays = $salary->working_days_per_month ?? 25;
        $fixedIncome = $salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance;
        $hourlyRate = ($workingDays > 0) ? ($fixedIncome / ($workingDays * 8)) : 0;
        
        // Match rate from OvertimeRate table if applicable
        $matchedRate = OvertimeRate::where(function($q) use ($user) {
            $q->where('division_id', $user->division_id)
              ->orWhereNull('division_id');
        })
        ->where('min_hours', '<=', $hours)
        ->where('max_hours', '>=', $hours)
        ->first();

        if ($matchedRate && $matchedRate->rate_amount > 0) {
            return round($hours * $matchedRate->rate_amount, 0);
        }
        
        $multiplier = 1.5;
        return round($hours * $hourlyRate * $multiplier, 0);
    }

    /**
     * Calculate and return duration based on start and end time.
     */
    public function calculateDuration()
    {
        if ($this->start_time && $this->end_time) {
            $start = Carbon::parse($this->start_time);
            $end = Carbon::parse($this->end_time);

            // Handle cross-day scenario (e.g. start at 22:00, end at 02:00 next day)
            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $diffInMinutes = $start->diffInMinutes($end);
            return round($diffInMinutes / 60, 2);
        }
        return 0;
    }
}
