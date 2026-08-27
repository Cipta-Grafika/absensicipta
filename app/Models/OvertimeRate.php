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
        'meal_min_start_time',
        'meal_min_duration',
        'meal_condition_type',
    ];

    protected $casts = [
        'min_hours' => 'float',
        'max_hours' => 'float',
        'rate_amount' => 'float',
        'meal_allowance' => 'float',
        'meal_min_duration' => 'float',
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
     * Calculate payment based on duration_hours and defined rate tiers using Progressive Tiering.
     * - Tiers are sorted by min_hours ASC.
     * - Hours within each tier step (e.g. 0-3 jam, 3-24 jam) are multiplied by that tier's rate.
     * - Meal allowance is dynamically validated against start_time, duration, and conditions.
     */
    public static function calculatePayForDuration(
        float $durationHours, 
        ?User $user = null, 
        ?string $startTime = null, 
        ?string $endTime = null, 
        ?string $overtimeDate = null
    ): array
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
        $allRates = self::where(function ($q) use ($userDivisionId) {
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

        if ($allRates->isNotEmpty()) {
            // Group rates by specificity score
            $groupedBySpecificity = $allRates->groupBy(function ($r) use ($userDivisionId, $userType) {
                $score = 0;
                if ($userDivisionId && $r->division_id == $userDivisionId) $score += 100;
                if ($r->employee_type === $userType) $score += 20;
                elseif ($r->employee_type === 'all' || is_null($r->employee_type)) $score += 5;
                return $score;
            });

            // Select highest specificity score group
            $bestScore = $groupedBySpecificity->keys()->max();
            $candidateRates = $groupedBySpecificity->get($bestScore)->sortBy('min_hours')->values();

            // Progressive tiering calculation
            $totalHourlyOrFlatPay = 0.0;
            $appliedMealAllowance = 0.0;
            $primaryRateAmount = 0.0;
            $previousMaxHours = 0.0;

            foreach ($candidateRates as $index => $rate) {
                $minH = (float) $rate->min_hours;
                $maxH = (float) $rate->max_hours;

                // Step start bound (for the first tier starting at > 0, begin from 0)
                $stepStart = ($index === 0 && $minH > 0) ? 0.0 : max($minH, $previousMaxHours);
                $stepEnd = $maxH;

                if ($durationHours > $stepStart) {
                    $hoursInThisStep = min($durationHours, $stepEnd) - $stepStart;

                    if ($hoursInThisStep > 0) {
                        $rateAmount = (float) $rate->rate_amount;
                        if ($primaryRateAmount == 0.0) {
                            $primaryRateAmount = $rateAmount;
                        }

                        if ($rate->rate_type === 'flat_package') {
                            $totalHourlyOrFlatPay += $rateAmount;
                        } else {
                            $totalHourlyOrFlatPay += ($hoursInThisStep * $rateAmount);
                        }

                        // Evaluate dynamic meal allowance eligibility
                        if ((float)($rate->meal_allowance ?? 0.0) > 0) {
                            $isMealApplicable = true;

                            // 1. Min duration check (if specified)
                            if ($rate->meal_min_duration !== null && (float) $rate->meal_min_duration > 0) {
                                if ($durationHours < (float) $rate->meal_min_duration) {
                                    $isMealApplicable = false;
                                }
                            }

                            // 2. Start time / time window check (if specified and startTime provided)
                            if ($isMealApplicable && !empty($rate->meal_min_start_time) && !empty($startTime)) {
                                $rateTime = substr(trim($rate->meal_min_start_time), 0, 5); // '17:00'
                                $actualStart = substr(trim($startTime), 0, 5); // '17:00'

                                if ($rate->meal_condition_type === 'crosses_time' && !empty($endTime)) {
                                    $actualEnd = substr(trim($endTime), 0, 5);
                                    // Crosses condition: started at/before target time and ended after target time, OR started >= target time
                                    if (!($actualStart >= $rateTime || ($actualStart <= $rateTime && $actualEnd > $rateTime))) {
                                        $isMealApplicable = false;
                                    }
                                } elseif ($rate->meal_condition_type === 'always') {
                                    $isMealApplicable = true;
                                } else {
                                    // Default 'start_time_gte'
                                    if ($actualStart < $rateTime) {
                                        $isMealApplicable = false;
                                    }
                                }
                            }

                            if ($isMealApplicable) {
                                $appliedMealAllowance = (float) $rate->meal_allowance;
                            }
                        }
                    }
                }

                $previousMaxHours = max($previousMaxHours, $maxH);
            }

            // If duration exceeds highest tier max_hours, calculate extra hours at highest tier's rate
            $highestRate = $candidateRates->last();
            if ($highestRate && $durationHours > $previousMaxHours) {
                $overflowHours = $durationHours - $previousMaxHours;
                $highestRateAmount = (float) $highestRate->rate_amount;
                if ($highestRate->rate_type !== 'flat_package') {
                    $totalHourlyOrFlatPay += ($overflowHours * $highestRateAmount);
                }
            }

            $totalPay = round($totalHourlyOrFlatPay + $appliedMealAllowance, 2);

            return [
                'applied_rate_amount' => $primaryRateAmount > 0 ? $primaryRateAmount : (float) ($highestRate->rate_amount ?? 0),
                'meal_allowance' => $appliedMealAllowance,
                'total_pay' => $totalPay,
            ];
        }

        // Fallback to User's salary default overtime_rate if set
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
