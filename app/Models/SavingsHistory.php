<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsHistory extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'savings_id',
        'mandatory_savings',
        'secondary_savings',
        'total_mandatory',
        'total_secondary',
        'total_savings',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savings()
    {
        return $this->belongsTo(Saving::class, 'savings_id');
    }
}
