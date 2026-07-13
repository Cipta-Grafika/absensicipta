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
        'reason',
        'status',
        'approved_by',
        'approval_date',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'approval_date' => 'datetime',
        'duration_hours' => 'float',
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
