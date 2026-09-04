<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorDeduction extends Model
{
    use HasFactory, HasUlids;

    protected static function booted()
    {
        static::saved(function ($ed) {
            \App\Services\DeductionNotificationService::notify($ed->user_id);
        });

        static::deleted(function ($ed) {
            \App\Services\DeductionNotificationService::notify($ed->user_id);
        });
    }

    protected $table = 'error_deductions';

    protected $fillable = [
        'user_id',
        'period_month',
        'error_date',
        'error_title',
        'description',
        'total_error_cost',
        'amount',
        'deduction_source',
        'status',
        'is_applied',
        'payroll_id',
        'saving_transaction_id',
        'created_by',
    ];

    protected $casts = [
        'error_date' => 'date',
        'total_error_cost' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_applied' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function savingTransaction()
    {
        return $this->belongsTo(SavingTransaction::class, 'saving_transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDeductionSourceLabelAttribute(): string
    {
        return match ($this->deduction_source) {
            'payroll' => 'Potong Gaji Bulanan',
            'syirkah_mandatory' => 'Syirkah Wajib',
            'syirkah_secondary' => 'Syirkah SSR (Sukarela)',
            'syirkah_all' => 'Total Saldo Syirkah',
            default => 'Potong Gaji',
        };
    }
}
