<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_hours',
        'max_hours',
        'rate_amount',
        'rate_type',
        'division_id',
    ];

    protected $casts = [
        'min_hours' => 'float',
        'max_hours' => 'float',
        'rate_amount' => 'float',
    ];

    /**
     * Relationship with Division
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Scope rates accessible for a user (own division + global).
     */
    public function scopeForUser($query, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $query;
        }

        if ($user->isSuperadmin) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            if ($user->division_id) {
                $q->where('division_id', $user->division_id)
                  ->orWhereNull('division_id');
            } else {
                $q->whereNull('division_id');
            }
        });
    }

    /**
     * Scope rates manageable by an Admin in HR Master Data.
     */
    public function scopeForAdminManagement($query, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $query;
        }

        if ($user->isSuperadmin) {
            return $query;
        }

        if ($user->division_id) {
            return $query->where('division_id', $user->division_id);
        }

        return $query->whereNull('division_id');
    }

    /**
     * Calculate payment based on duration_hours and defined rate tiers.
     * Prioritizes division-specific rates first, then falls back to global rates.
     */
    public static function calculatePayForDuration(float $durationHours, ?User $user = null): array
    {
        if ($durationHours <= 0) {
            return [
                'applied_rate_amount' => 0.0,
                'total_pay' => 0.0,
            ];
        }

        $rate = null;

        // Priority 1: Check Division-Specific Rates if user belongs to a division
        if ($user && $user->division_id) {
            $divisionRatesQuery = self::where('division_id', $user->division_id);

            if ($divisionRatesQuery->exists()) {
                // Find exact range match in division rates
                $rate = (clone $divisionRatesQuery)
                    ->where('min_hours', '<=', $durationHours)
                    ->where('max_hours', '>=', $durationHours)
                    ->orderBy('min_hours', 'asc')
                    ->first();

                // Nearest lower tier in division rates
                if (!$rate) {
                    $rate = (clone $divisionRatesQuery)
                        ->where('min_hours', '<=', $durationHours)
                        ->orderBy('min_hours', 'desc')
                        ->first();
                }

                // Max tier in division rates
                if (!$rate) {
                    $rate = (clone $divisionRatesQuery)
                        ->orderBy('max_hours', 'desc')
                        ->first();
                }
            }
        }

        // Priority 2: Fallback to Global Rates (division_id is null)
        if (!$rate) {
            $globalRatesQuery = self::whereNull('division_id');

            $rate = (clone $globalRatesQuery)
                ->where('min_hours', '<=', $durationHours)
                ->where('max_hours', '>=', $durationHours)
                ->orderBy('min_hours', 'asc')
                ->first();

            if (!$rate) {
                $rate = (clone $globalRatesQuery)
                    ->where('min_hours', '<=', $durationHours)
                    ->orderBy('min_hours', 'desc')
                    ->first();
            }

            if (!$rate) {
                $rate = (clone $globalRatesQuery)
                    ->orderBy('max_hours', 'desc')
                    ->first();
            }
        }

        if ($rate) {
            $appliedRate = (float) $rate->rate_amount;
            $totalPay = ($rate->rate_type === 'flat_package')
                ? $appliedRate
                : round($durationHours * $appliedRate, 2);

            return [
                'applied_rate_amount' => $appliedRate,
                'total_pay' => $totalPay,
            ];
        }

        // Priority 3: Ultimate fallback to User's salary default overtime_rate if set
        $fallbackRate = 0.0;
        if ($user && $user->salary && $user->salary->overtime_rate) {
            $fallbackRate = (float) $user->salary->overtime_rate;
        }

        return [
            'applied_rate_amount' => $fallbackRate,
            'total_pay' => round($durationHours * $fallbackRate, 2),
        ];
    }
}
