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

    public function setBankAccountAttribute($value)
    {
        $this->attributes['bank_account'] = is_string($value)
            ? preg_replace('/[\x{200B}-\x{200D}\x{200E}\x{200F}\x{202A}-\x{202E}\x{FEFF}]/u', '', $value)
            : $value;
    }

    public function getBankAccountAttribute($value)
    {
        return is_string($value)
            ? preg_replace('/[\x{200B}-\x{200D}\x{200E}\x{200F}\x{202A}-\x{202E}\x{FEFF}]/u', '', $value)
            : $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
