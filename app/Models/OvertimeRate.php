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
        'employee_type',
        'meal_allowance',
    ];

    protected $casts = [
        'min_hours' => 'float',
        'max_hours' => 'float',
        'rate_amount' => 'float',
        'meal_allowance' => 'float',
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
     * Prioritizes division-specific and employee-type specific rates first.
     */
    public static function calculatePayForDuration(float $durationHours, ?User $user = null): array
    {
        if ($durationHours <= 0) {
            return [
                'applied_rate_amount' => 0.0,
                'meal_allowance' => 0.0,
                'total_pay' => 0.0,
            ];
        }

        $userDivisionId = $user?->division_id;
        $userType = $user?->type ?? 'full-time';

        // Query candidate rates matching division (User's division or global null)
        // and matching employee_type (User's type or 'all' or null)
        $candidateRates = self::where(function ($q) use ($userDivisionId) {
            if ($userDivisionId) {
                $q->where('division_id', $userDivisionId)
                  ->orWhereNull('division_id');
            } else {
                $q->whereNull('division_id');
            }
        })
        ->where(function ($q) use ($userType) {
            $q->where('employee_type', $userType)
              ->orWhere('employee_type', 'all')
              ->orWhereNull('employee_type');
        })
        ->get();

        if ($candidateRates->isNotEmpty()) {
            // Find exact duration range match first
            $matched = $candidateRates->filter(fn($r) => $r->min_hours <= $durationHours && $r->max_hours >= $durationHours);

            if ($matched->isEmpty()) {
                // Nearest lower tier
                $matched = $candidateRates->filter(fn($r) => $r->min_hours <= $durationHours);
            }

            if ($matched->isEmpty()) {
                $matched = $candidateRates;
            }

            // Score specificity: Exact division (+10), Exact employee_type (+5), All/null type (+1)
            $rate = $matched->sortByDesc(function ($r) use ($userDivisionId, $userType) {
                $score = 0;
                if ($userDivisionId && $r->division_id == $userDivisionId) {
                    $score += 10;
                }
                if ($r->employee_type === $userType) {
                    $score += 5;
                } elseif ($r->employee_type === 'all' || is_null($r->employee_type)) {
                    $score += 1;
                }
                return $score;
            })->first();

            if ($rate) {
                $appliedRate = (float) $rate->rate_amount;
                $mealAllowance = (float) ($rate->meal_allowance ?? 0.0);
                $hourlyOrFlatPay = ($rate->rate_type === 'flat_package')
                    ? $appliedRate
                    : round($durationHours * $appliedRate, 2);

                $totalPay = round($hourlyOrFlatPay + $mealAllowance, 2);

                return [
                    'applied_rate_amount' => $appliedRate,
                    'meal_allowance' => $mealAllowance,
                    'total_pay' => $totalPay,
                ];
            }
        }

        // Priority 3: Fallback to User's salary default overtime_rate if set
        $fallbackRate = 0.0;
        if ($user && $user->salary && $user->salary->overtime_rate) {
            $fallbackRate = (float) $user->salary->overtime_rate;
        }

        return [
            'applied_rate_amount' => $fallbackRate,
            'meal_allowance' => 0.0,
            'total_pay' => round($durationHours * $fallbackRate, 2),
        ];
    }
}
