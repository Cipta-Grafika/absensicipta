<?php

namespace App\Models;

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
        'amount',
        'balance_before',
        'balance_after',
        'transaction_date',
        'description',
        'reference_number',
        'proof_attachment_path',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
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

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->amount, 0, ',', '.');
    }

    public function getFormattedBalanceAfterAttribute(): string
    {
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
}

