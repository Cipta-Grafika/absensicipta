<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory, HasTimestamps;

    protected $fillable = [
        'name',
        'off_days',
    ];

    protected function casts(): array
    {
        return [
            'off_days' => 'array',
        ];
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }
}
