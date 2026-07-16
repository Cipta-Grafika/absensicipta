<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class LoanInstallment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'loan_id',
        'amount_paid',
        'payment_method',
        'payroll_id',
        'saving_transaction_id',
        'status',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function savingTransaction()
    {
        return $this->belongsTo(SavingTransaction::class);
    }
}
