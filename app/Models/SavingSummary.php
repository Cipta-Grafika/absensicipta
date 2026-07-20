<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingSummary extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'saving_summaries';

    protected $fillable = [
        'user_id',
        'savings_id',
        'total_mandatory',
        'total_secondary',
    ];

    /**
     * Get the user that owns the summary.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the saving program associated with this summary.
     */
    public function masterSaving()
    {
        return $this->belongsTo(Saving::class, 'savings_id');
    }
}
