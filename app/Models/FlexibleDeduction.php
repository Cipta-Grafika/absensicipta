<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlexibleDeduction extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'flexible_deductions';

    protected $fillable = [
        'program_id',
        'user_id',
        'period_month',
        'amount',
        'deduction_source',
        'notes',
        'is_applied',
        'payroll_id',
        'saving_transaction_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_applied' => 'boolean',
        'deduction_source' => 'string',
    ];

    public function getDeductionSourceLabelAttribute(): string
    {
        return match ($this->deduction_source) {
            'syirkah_mandatory' => 'Syirkah Wajib',
            'syirkah_secondary' => 'Syirkah SSR',
            'syirkah_all' => 'Wajib + SSR',
            default => 'Potong Gaji',
        };
    }

    public function program()
    {
        return $this->belongsTo(FlexibleDeductionProgram::class, 'program_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function savingTransaction()
    {
        return $this->belongsTo(SavingTransaction::class, 'saving_transaction_id');
    }
}
