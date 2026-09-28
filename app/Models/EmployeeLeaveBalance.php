<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EmployeeLeaveBalance extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'employee_leave_balances';

    protected $fillable = [
        'user_id',
        'year',
        'initial_quota',
        'carry_forward',
        'adjustment',
        'used_quota',
        'expired_at',
        'note',
    ];

    protected $casts = [
        'year' => 'integer',
        'initial_quota' => 'integer',
        'carry_forward' => 'integer',
        'adjustment' => 'integer',
        'used_quota' => 'integer',
        'expired_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get total effective quota (initial + carry forward + adjustment).
     */
    public function getTotalQuotaAttribute(): int
    {
        return (int) ($this->initial_quota + $this->carry_forward + $this->adjustment);
    }

    /**
     * Get remaining quota.
     */
    public function getRemainingQuotaAttribute(): int
    {
        return (int) ($this->total_quota - $this->used_quota);
    }

    /**
     * Get usage percentage (0 - 100%).
     */
    public function getUsagePercentageAttribute(): float
    {
        $total = $this->total_quota;
        if ($total <= 0) {
            return $this->used_quota > 0 ? 100.0 : 0.0;
        }
        return (float) min(100.0, round(($this->used_quota / $total) * 100, 1));
    }

    /**
     * Recalculate and update the used quota based on attendances across the full year (January - December).
     */
    public function syncUsedQuota(): int
    {
        $startDate = sprintf('%04d-01-01', $this->year);
        $endDate = sprintf('%04d-12-31', $this->year);

        $usedCount = Attendance::where('user_id', $this->user_id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])
                  ->orWhereYear('date', $this->year);
            })
            ->whereIn('status', ['leave'])
            ->count();

        $this->update(['used_quota' => $usedCount]);

        return $usedCount;
    }

    /**
     * Get or create leave balance record for a user and year.
     */
    public static function getOrCreateForUser(User|string $user, ?int $year = null): self
    {
        $userId = $user instanceof User ? $user->id : $user;
        $year = $year ?: (int) date('Y');

        $balance = static::where('user_id', $userId)->where('year', $year)->first();

        if (!$balance) {
            $userModel = $user instanceof User ? $user : User::find($userId);
            $initialQuota = 12;

            if ($userModel && $userModel->salary && $userModel->salary->annual_leave_quota !== null) {
                $initialQuota = (int) $userModel->salary->annual_leave_quota;
            }

            $balance = static::create([
                'user_id' => $userId,
                'year' => $year,
                'initial_quota' => $initialQuota,
                'carry_forward' => 0,
                'adjustment' => 0,
                'used_quota' => 0,
                'note' => 'Inisialisasi kuota otomatis ' . $year,
            ]);

            $balance->syncUsedQuota();
        }

        return $balance;
    }

    /**
     * Sync leave balance for a specific user and year.
     */
    public static function syncForUserAndYear(string $userId, int $year): ?self
    {
        $balance = static::where('user_id', $userId)->where('year', $year)->first();

        if (!$balance) {
            $user = User::find($userId);
            if ($user && $user->group === 'user') {
                return static::getOrCreateForUser($user, $year);
            }
            return null;
        }

        $balance->syncUsedQuota();
        return $balance;
    }

    /**
     * Sync all active employees' leave balances for a specific year.
     */
    public static function syncAllForYear(?int $year = null): void
    {
        $year = $year ?: (int) date('Y');
        $employees = User::where('group', 'user')
            ->whereIn('status', ['active', 'suspend'])
            ->with('salary')
            ->get();

        foreach ($employees as $emp) {
            static::syncForUserAndYear($emp->id, $year);
        }
    }
}
