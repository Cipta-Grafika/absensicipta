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
        'approval_date',
        'rejection_reason',
        'description',
    ];

    protected $casts = [
        'approval_date' => 'datetime',
        'loan_amount' => 'float',
        'installment_amount' => 'float',
        'remaining_balance' => 'float',
        'tenor_months' => 'integer',
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }

    public function scopePaidOff($query)
    {
        return $query->where('status', 'paid_off');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
