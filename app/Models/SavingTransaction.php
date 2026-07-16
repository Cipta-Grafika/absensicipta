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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function masterSaving()
    {
        return $this->belongsTo(Saving::class, 'savings_id');
    }
}
