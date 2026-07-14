<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'payment_name',
        'bank_account',
        'account_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
