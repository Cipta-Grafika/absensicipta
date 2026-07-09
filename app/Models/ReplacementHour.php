<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplacementHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'replaced_date',
        'replacement_date',
        'start_hour',
        'end_hour',
        'shift_id',
        'reason',
        'attachment',
        'status',
        'approved_by',
    ];

    /**
     * Get the user that requested the replacement.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin/superadmin who approved or rejected the request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the shift being replaced.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Calculate duration in minutes between start_hour and end_hour
     */
    public function getDurationMinutesAttribute()
    {
        if (!$this->start_hour || !$this->end_hour) {
            return 0;
        }
        
        $start = Carbon::parse($this->start_hour);
        $end = Carbon::parse($this->end_hour);
        
        return abs($end->diffInMinutes($start));
    }
    
    /**
     * Format duration nicely (e.g. 2 jam 30 menit)
     */
    public function getFormattedDurationAttribute()
    {
        $minutes = $this->duration_minutes;
        if ($minutes <= 0) return '0 menit';
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        $result = [];
        if ($hours > 0) $result[] = $hours . ' jam';
        if ($remainingMinutes > 0) $result[] = $remainingMinutes . ' menit';
        
        return implode(' ', $result);
    }
}
