<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SavingTransaction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'savings_id',
        'transaction_type',
        'mandatory_amount',
        'secondary_amount',
        'balance_mandatory',
        'balance_secondary',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'approved_by',
        'approval_date',
        'rejection_reason',
    ];

    protected $casts = [
        'approval_date' => 'datetime',
        'mandatory_amount' => 'float',
        'secondary_amount' => 'float',
        'balance_mandatory' => 'float',
        'balance_secondary' => 'float',
    ];

    protected static function booted()
    {
        static::created(function ($transaction) {
            self::syncSummary($transaction);
        });

        static::updated(function ($transaction) {
            self::syncSummary($transaction);
        });

        static::deleted(function ($transaction) {
            self::syncSummary($transaction);
        });
    }

    public static function syncSummary($transaction)
    {
        if (!$transaction->user_id || !$transaction->savings_id) return;

        $depMan = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('mandatory_amount');
        $wdMan = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('mandatory_amount');
        
        $depSec = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('secondary_amount');
        $wdSec = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('secondary_amount');

        \App\Models\SavingSummary::updateOrCreate(
            [
                'user_id' => $transaction->user_id,
                'savings_id' => $transaction->savings_id,
            ],
            [
                'total_mandatory' => max(0, $depMan - $wdMan),
                'total_secondary' => max(0, $depSec - $wdSec),
            ]
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function masterSaving()
    {
        return $this->belongsTo(Saving::class, 'savings_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
