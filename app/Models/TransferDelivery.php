<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferDelivery extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'transfer_deliveries';

    public const STATUS_NOT_SENT = 'NOT_SENT';
    public const STATUS_QUEUED = 'QUEUED';
    public const STATUS_SENDING = 'SENDING';
    public const STATUS_SENT = 'SENT';
    public const STATUS_RETRYING = 'RETRYING';
    public const STATUS_FAILED_VALIDATION = 'FAILED_VALIDATION';
    public const STATUS_FAILED_AUTH = 'FAILED_AUTH';
    public const STATUS_FAILED_SEND = 'FAILED_SEND';

    protected $fillable = [
        'application_id',
        'id_pengajuan',
        'delivery_status',
        'id_ledger',
        'status_ledger',
        'retry_count',
        'first_attempt_at',
        'first_failure_at',
        'last_attempt_at',
        'next_retry_at',
        'last_http_status',
        'last_error_code',
        'last_error_message',
        'payload',
        'response_body',
        'sent_at',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'first_attempt_at' => 'datetime',
        'first_failure_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'last_http_status' => 'integer',
        'payload' => 'array',
        'response_body' => 'array',
        'sent_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(SavingWithdrawal::class, 'application_id');
    }

    public function scopeQueued($query)
    {
        return $query->whereIn('delivery_status', [self::STATUS_QUEUED, self::STATUS_NOT_SENT]);
    }

    public function scopePendingRetry($query)
    {
        return $query->where('delivery_status', self::STATUS_RETRYING)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now());
    }

    public function isSuccessful(): bool
    {
        return $this->delivery_status === self::STATUS_SENT;
    }

    public function getIsTestAttribute(): bool
    {
        return str_starts_with((string) $this->id_pengajuan, 'UJI-');
    }
}
