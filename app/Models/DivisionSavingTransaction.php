<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionSavingTransaction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'transaction_code',
        'division_id',
        'wallet_id',
        'transaction_type',
        'status',
        'amount',
        'balance_before',
        'balance_after',
        'transaction_date',
        'description',
        'reference_number',
        'proof_attachment_path',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(DivisionWallet::class, 'wallet_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->amount, 0, ',', '.');
    }

    public function getFormattedBalanceBeforeAttribute(): string
    {
        if ($this->balance_before === null) {
            return '-';
        }
        return 'Rp ' . number_format((float) $this->balance_before, 0, ',', '.');
    }

    public function getFormattedBalanceAfterAttribute(): string
    {
        if ($this->balance_after === null) {
            return '-';
        }
        return 'Rp ' . number_format((float) $this->balance_after, 0, ',', '.');
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->transaction_type === 'credit';
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->transaction_type === 'debit';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }
}
