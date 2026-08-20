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
        'break',
        'duration_hours',
        'applied_rate_amount',
        'total_pay',
        'reason',
        'status',
        'approved_by',
        'approval_date',
        'paid_at',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'approval_date' => 'datetime',
        'paid_at' => 'datetime',
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
     * Accessor for overtime_pay attribute (dynamically calculated from master data)
     */
    public function getOvertimePayAttribute()
    {
        $user = $this->employee ?: User::find($this->employee_id);
        $hours = (float) ($this->duration_hours ?? $this->calculateDuration());
        if ($hours <= 0) return 0;

        $payData = OvertimeRate::calculatePayForDuration($hours, $user);
        return $payData['total_pay'] > 0 ? $payData['total_pay'] : ($this->attributes['total_pay'] ?? 0);
    }

    /**
     * Convert break duration string (HH:MM or minutes string) to integer minutes
     */
    public static function convertBreakToMinutes(?string $breakStr): int
    {
        if (empty($breakStr)) return 0;
        $breakStr = trim($breakStr);

        if (str_contains($breakStr, ':')) {
            $parts = explode(':', $breakStr);
            $hours = (int) ($parts[0] ?? 0);
            $minutes = (int) ($parts[1] ?? 0);
            return ($hours * 60) + $minutes;
        }

        if (is_numeric($breakStr)) {
            return (int) $breakStr;
        }

        return 0;
    }

    /**
     * Accessor for formatted duration (e.g. 3 jam 30 menit (Istirahat 30 mnt))
     */
    public function getFormattedDurationAttribute()
    {
        $hours = $this->duration_hours ?? $this->calculateDuration();
        if ($hours <= 0) return '0 jam';
        
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        
        $breakText = '';
        if (!empty($this->break)) {
            $bMins = self::convertBreakToMinutes($this->break);
            if ($bMins > 0) {
                $bH = floor($bMins / 60);
                $bM = $bMins % 60;
                if ($bH > 0 && $bM > 0) {
                    $breakText = " (Istirahat {$bH}j {$bM}m)";
                } elseif ($bH > 0) {
                    $breakText = " (Istirahat {$bH} jam)";
                } else {
                    $breakText = " (Istirahat {$bM} mnt)";
                }
            }
        }

        if ($h > 0 && $m > 0) {
            return "{$h} jam {$m} menit" . $breakText;
        } elseif ($h > 0) {
            return "{$h} jam" . $breakText;
        }
        return "{$m} menit" . $breakText;
    }

    /**
     * Calculate estimated pay for overtime request
     */
    public function calculateEstimatedPay()
    {
        $user = $this->employee ?: User::find($this->employee_id);
        $hours = (float) ($this->duration_hours ?? $this->calculateDuration());
        if ($hours <= 0) return 0;

        $payData = OvertimeRate::calculatePayForDuration($hours, $user);
        if ($payData['total_pay'] > 0) {
            return $payData['total_pay'];
        }

        if ($this->total_pay && $this->total_pay > 0) {
            return $this->total_pay;
        }

        if (!$user || !$user->salary) {
            $defaultRate = OvertimeRate::first()?->rate_amount ?? 20000;
            return round($hours * $defaultRate, 0);
        }

        $salary = $user->salary;
        $workingDays = $salary->working_days_per_month ?? 25;
        $fixedIncome = $salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance;
        $hourlyRate = ($workingDays > 0) ? ($fixedIncome / ($workingDays * 8)) : 0;
        
        return round($hours * $hourlyRate * 1.5, 0);
    }

    /**
     * Calculate and return duration based on start and end time minus break.
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

            if (!empty($this->break)) {
                $breakMinutes = self::convertBreakToMinutes($this->break);
                $diffInMinutes = max(0, $diffInMinutes - $breakMinutes);
            }

            return round($diffInMinutes / 60, 2);
        }
        return 0;
    }
}
