<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingWithdrawal extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'saving_withdrawals';

    protected $fillable = [
        'user_id',
        'savings_id',
        'withdrawal_type',
        'mandatory_amount',
        'secondary_amount',
        'total_amount',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'rejection_reason',
        'saving_transaction_id',
    ];

    protected $casts = [
        'mandatory_amount' => 'float',
        'secondary_amount' => 'float',
        'total_amount' => 'float',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($withdrawal) {
            \App\Services\DeductionNotificationService::notify($withdrawal->user_id);
        });

        static::deleted(function ($withdrawal) {
            \App\Services\DeductionNotificationService::notify($withdrawal->user_id);
        });
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

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function savingTransaction()
    {
        return $this->belongsTo(SavingTransaction::class, 'saving_transaction_id');
    }

    public function getWithdrawalTypeLabelAttribute(): string
    {
        return match ($this->withdrawal_type) {
            'full' => 'Syirkah Full (Wajib + SSR)',
            'mandatory' => 'Syirkah Wajib',
            'secondary' => 'Syirkah Sukarela (SSR)',
            default => 'Syirkah',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => [
                'label' => 'PENDING',
                'bg' => 'bg-amber-100 dark:bg-amber-950/60',
                'text' => 'text-amber-800 dark:text-amber-300',
                'border' => 'border-amber-300 dark:border-amber-800',
                'desc' => 'Menunggu Persetujuan Admin',
            ],
            'accepted' => [
                'label' => 'ACCEPTED',
                'bg' => 'bg-blue-100 dark:bg-blue-950/60',
                'text' => 'text-blue-800 dark:text-blue-300',
                'border' => 'border-blue-300 dark:border-blue-800',
                'desc' => 'Disetujui Admin, Menunggu Pembayaran',
            ],
            'paid' => [
                'label' => 'PAID',
                'bg' => 'bg-emerald-100 dark:bg-emerald-950/60',
                'text' => 'text-emerald-800 dark:text-emerald-300',
                'border' => 'border-emerald-300 dark:border-emerald-800',
                'desc' => 'Telah Selesai Dibayarkan',
            ],
            'rejected' => [
                'label' => 'REJECTED',
                'bg' => 'bg-rose-100 dark:bg-rose-950/60',
                'text' => 'text-rose-800 dark:text-rose-300',
                'border' => 'border-rose-300 dark:border-rose-800',
                'desc' => 'Pengajuan Ditolak',
            ],
            default => [
                'label' => strtoupper($this->status),
                'bg' => 'bg-gray-100 dark:bg-gray-800',
                'text' => 'text-gray-800 dark:text-gray-300',
                'border' => 'border-gray-300 dark:border-gray-700',
                'desc' => '-',
            ],
        };
    }
}
