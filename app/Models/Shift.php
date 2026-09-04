<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory, HasTimestamps;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'division_id',
    ];

    /**
     * Relationship with Division
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Scope shifts accessible by a user for selecting/viewing (own division + global).
     */
    public function scopeForUser($query, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $query;
        }

        if ($user->isSuperadmin) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            if ($user->division_id) {
                $q->where('division_id', $user->division_id)
                  ->orWhereNull('division_id');
            } else {
                $q->whereNull('division_id');
            }
        });
    }

    /**
     * Scope shifts manageable by an Admin in HR Master Data.
     */
    public function scopeForAdminManagement($query, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $query;
        }

        if ($user->isSuperadmin) {
            return $query;
        }

        if ($user->division_id) {
            return $query->where('division_id', $user->division_id);
        }

        return $query->whereNull('division_id');
    }

    /**
     * Calculate duration in minutes between start_time and end_time
     */
    public function getDurationMinutesAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        
        return abs($end->diffInMinutes($start));
    }

    /**
     * Get candidate shifts for a specific user using strict hierarchy:
     * 1. Lock to user's division shifts first if they exist.
     * 2. Fallback to global shifts only if no division shifts exist.
     */
    public static function getCandidateShiftsForUser($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return static::whereNull('division_id')->orderBy('start_time')->get();
        }

        if ($user->isSuperadmin) {
            return static::orderBy('start_time')->get();
        }

        if ($user->division_id) {
            $divisionShifts = static::where('division_id', $user->division_id)->orderBy('start_time')->get();
            if ($divisionShifts->isNotEmpty()) {
                return $divisionShifts;
            }
        }

        return static::whereNull('division_id')->orderBy('start_time')->get();
    }

    /**
     * Calculate the 2-hour check-in window and active state for this shift.
     */
    public function getCheckInWindowInfo(?\Illuminate\Support\Carbon $now = null): array
    {
        $now = $now ? $now->copy() : \Illuminate\Support\Carbon::now();
        $startStr = \Illuminate\Support\Carbon::parse($this->start_time ?? '08:00:00')->format('H:i:s');
        $endStr = \Illuminate\Support\Carbon::parse($this->end_time ?? '17:00:00')->format('H:i:s');

        $isOvernight = $endStr < $startStr;

        if ($isOvernight) {
            // e.g. 21:00 - 05:00
            if ($now->format('H:i:s') <= $endStr) {
                $shiftStart = \Illuminate\Support\Carbon::yesterday()->setTimeFromTimeString($startStr);
                $shiftEnd = \Illuminate\Support\Carbon::today()->setTimeFromTimeString($endStr);
            } else {
                $shiftStart = \Illuminate\Support\Carbon::today()->setTimeFromTimeString($startStr);
                $shiftEnd = \Illuminate\Support\Carbon::tomorrow()->setTimeFromTimeString($endStr);
            }
        } else {
            // Regular daytime shift, e.g. 08:00 - 17:00
            $shiftStart = \Illuminate\Support\Carbon::today()->setTimeFromTimeString($startStr);
            $shiftEnd = \Illuminate\Support\Carbon::today()->setTimeFromTimeString($endStr);
        }

        // Check-in window opens strictly 2 hours before shift start time
        $earliestCheckIn = $shiftStart->copy()->subHours(2);
        // Check-in window closes at shift end time
        $latestCheckIn = $shiftEnd->copy();

        $isOpen = $now->gte($earliestCheckIn) && $now->lte($latestCheckIn);

        $minutesToOpen = $now->lt($earliestCheckIn) ? $now->diffInMinutes($earliestCheckIn) : 0;
        $distanceToStartMinutes = abs($now->diffInMinutes($shiftStart, false));

        return [
            'shift_id' => $this->id,
            'name' => $this->name,
            'start_time' => $startStr,
            'end_time' => $endStr,
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'earliest_check_in' => $earliestCheckIn,
            'latest_check_in' => $latestCheckIn,
            'earliest_time_str' => $earliestCheckIn->format('H:i'),
            'start_time_str' => $shiftStart->format('H:i'),
            'end_time_str' => $shiftEnd->format('H:i'),
            'is_open' => $isOpen,
            'minutes_to_open' => $minutesToOpen,
            'distance_to_start_minutes' => $distanceToStartMinutes,
            'is_overnight' => $isOvernight,
        ];
    }
}
