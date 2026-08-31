<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxMaster extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'category',
        'code',
        'name',
        'min_gross_income',
        'max_gross_income',
        'rate_percentage',
        'description',
    ];

    protected $casts = [
        'min_gross_income' => 'float',
        'max_gross_income' => 'float',
        'rate_percentage' => 'float',
    ];

    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class, 'tax_master_id');
    }

    public function getFormattedRateAttribute(): string
    {
        return $this->rate_percentage . '%';
    }

    public function getFormattedRangeAttribute(): string
    {
        if ($this->max_gross_income === null) {
            return '> Rp ' . number_format($this->min_gross_income, 0, ',', '.');
        }
        if ($this->min_gross_income <= 0) {
            return '<= Rp ' . number_format($this->max_gross_income, 0, ',', '.');
        }
        return 'Rp ' . number_format($this->min_gross_income, 0, ',', '.') . ' - Rp ' . number_format($this->max_gross_income, 0, ',', '.');
    }
}
