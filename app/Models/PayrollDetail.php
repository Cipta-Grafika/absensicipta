<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory, HasUlids;

    protected static function booted()
    {
        static::saved(function ($detail) {
            \App\Services\DeductionNotificationService::notify($detail->payroll?->employee_id);
        });

        static::deleted(function ($detail) {
            \App\Services\DeductionNotificationService::notify($detail->payroll?->employee_id);
        });
    }

    protected $fillable = [
        'payroll_id',
        'type', // 'earning' or 'deduction'
        'name',
        'amount',
        'description',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
