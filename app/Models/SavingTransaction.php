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

        $depMan = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('transaction_type', 'deposit')->sum('mandatory_amount');
        $wdMan = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('transaction_type', 'withdrawal')->sum('mandatory_amount');
        
        $depSec = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('transaction_type', 'deposit')->sum('secondary_amount');
        $wdSec = self::where('user_id', $transaction->user_id)->where('savings_id', $transaction->savings_id)->where('transaction_type', 'withdrawal')->sum('secondary_amount');

        \App\Models\SavingSummary::updateOrCreate(
            [
                'user_id' => $transaction->user_id,
                'savings_id' => $transaction->savings_id,
            ],
            [
                'total_mandatory' => $depMan - $wdMan,
                'total_secondary' => $depSec - $wdSec,
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
}
