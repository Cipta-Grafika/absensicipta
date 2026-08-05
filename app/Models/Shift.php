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
}
