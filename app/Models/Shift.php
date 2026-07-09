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
    ];

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
