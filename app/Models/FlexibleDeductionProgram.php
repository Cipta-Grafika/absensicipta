<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlexibleDeductionProgram extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'flexible_deduction_programs';

    protected $fillable = [
        'name',
        'period_month',
        'description',
        'status',
        'created_by',
    ];

    public function deductions()
    {
        return $this->hasMany(FlexibleDeduction::class, 'program_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalDeductionsAttribute(): float
    {
        return (float) $this->deductions()->sum('amount');
    }

    public function getTotalEmployeesAttribute(): int
    {
        return $this->deductions()->where('amount', '>', 0)->count();
    }
}
