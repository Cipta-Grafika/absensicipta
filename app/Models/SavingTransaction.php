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
        'transfer_proof',
        'created_at',
        'updated_at',
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
            if ($transaction->transfer_proof && $transaction->reference_type !== 'saving_withdrawal' && \Illuminate\Support\Facades\Storage::disk('public')->exists($transaction->transfer_proof)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($transaction->transfer_proof);
            }
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

    public function savingWithdrawal()
    {
        return $this->belongsTo(SavingWithdrawal::class, 'reference_id');
    }

    public function getEffectiveTransferProofAttribute(): ?string
    {
        if (!empty($this->transfer_proof)) {
            return $this->transfer_proof;
        }

        if ($this->reference_type === 'saving_withdrawal' && $this->relationLoaded('savingWithdrawal') && $this->savingWithdrawal) {
            return $this->savingWithdrawal->transfer_proof;
        }

        if ($this->reference_type === 'saving_withdrawal' && $this->reference_id) {
            return $this->savingWithdrawal?->transfer_proof;
        }

        return null;
    }

    public function getEffectiveTransferProofUrlAttribute(): ?string
    {
        $proof = $this->effective_transfer_proof;
        if (!$proof) {
            return null;
        }
        $base = '';
        try {
            if (function_exists('request') && request()) {
                $base = request()->getBasePath() ?: '';
            }
        } catch (\Throwable $e) {}
        return $base . '/storage/' . ltrim($proof, '/');
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
