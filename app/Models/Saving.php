<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saving extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'savings_name',
        'mandatory_savings',
        'secondary_savings',
    ];

    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class, 'savings_id');
    }
}
