<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Loan extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'loan_amount',
        'tenor_months',
        'installment_amount',
        'remaining_balance',
        'status',
        'approved_by',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class);
    }
}
