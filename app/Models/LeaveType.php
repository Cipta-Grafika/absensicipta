<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $table = 'leave_types';

    protected $fillable = [
        'code',
        'name',
        'default_days',
        'deducts_annual_quota',
        'requires_attachment',
        'is_active',
        'description',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'deducts_annual_quota' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
