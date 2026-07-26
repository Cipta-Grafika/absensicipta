<?php

namespace App\Services;

use App\Models\Division;
use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceScheduleService
{
    /**
     * Determine if a user is scheduled to work on a given date.
     */
    public static function isWorkingDay(User $user, Carbon|string $date): bool
    {
        return static::getScheduleDetails($user, $date)['is_working_day'];
    }

    /**
     * Get detailed schedule evaluation for a user on a given date.
     */
    public static function getScheduleDetails(User $user, Carbon|string $date): array
    {
        $carbonDate = is_string($date) ? Carbon::parse($date) : $date->copy();
        $dateStr = $carbonDate->format('Y-m-d');
        $dayName = strtolower($carbonDate->format('l'));

        // Priority 1: Specific User Work Schedule for Date
        $workSchedule = WorkSchedule::where('user_id', $user->id)
            ->where('date', $dateStr)
            ->first();

        if ($workSchedule !== null) {
            return [
                'is_working_day' => (bool) $workSchedule->is_working_day,
                'reason' => $workSchedule->is_working_day ? 'Roster Work Day' : 'Roster Off Day',
                'type' => 'work_schedule',
            ];
        }

        // Priority 2: General Holiday
        $generalHoliday = Holiday::where('type', 'general')
            ->where('date', $dateStr)
            ->first();

        if ($generalHoliday) {
            return [
                'is_working_day' => false,
                'reason' => $generalHoliday->name,
                'type' => 'general_holiday',
            ];
        }

        // Priority 3: Division Holiday
        if ($user->division_id) {
            $divisionHoliday = Holiday::where('type', 'division')
                ->where('division_id', $user->division_id)
                ->where('date', $dateStr)
                ->first();

            if ($divisionHoliday) {
                return [
                    'is_working_day' => false,
                    'reason' => $divisionHoliday->name,
                    'type' => 'division_holiday',
                ];
            }
        }

        // Priority 4: Custom User Holiday
        $customHoliday = Holiday::where('type', 'custom')
            ->where('date', $dateStr)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->first();

        if ($customHoliday) {
            return [
                'is_working_day' => false,
                'reason' => $customHoliday->name,
                'type' => 'custom_holiday',
            ];
        }

        // Priority 5: Custom User Recurring Off-Days
        $userOffDays = is_array($user->off_days) ? array_map('strtolower', $user->off_days) : null;
        if (!empty($userOffDays)) {
            if (in_array($dayName, $userOffDays, true)) {
                return [
                    'is_working_day' => false,
                    'reason' => 'User Off Day',
                    'type' => 'user_off_day',
                ];
            }
        } else {
            // Priority 6: Division Recurring Off-Days (only if user off_days is null/empty)
            $division = $user->relationLoaded('division') ? $user->division : ($user->division_id ? Division::find($user->division_id) : null);
            $divisionOffDays = ($division && is_array($division->off_days)) ? array_map('strtolower', $division->off_days) : null;

            if (!empty($divisionOffDays)) {
                if (in_array($dayName, $divisionOffDays, true)) {
                    return [
                        'is_working_day' => false,
                        'reason' => 'Division Off Day',
                        'type' => 'division_off_day',
                    ];
                }
            } else {
                // Priority 7: Default Sunday Off-Day (only if division off_days is also null/empty)
                if ($carbonDate->isSunday()) {
                    return [
                        'is_working_day' => false,
                        'reason' => 'Default Sunday Off',
                        'type' => 'default_sunday_off',
                    ];
                }
            }
        }

        // Priority 8: Working Day
        return [
            'is_working_day' => true,
            'reason' => 'Regular Working Day',
            'type' => 'working_day',
        ];
    }

    /**
     * Build an optimized bulk context for pre-evaluating schedules across multiple users and dates.
     * Prevents N+1 queries during payroll calculations.
     */
    public static function buildContext(Collection|array $users, Carbon|string $startDate, Carbon|string $endDate): AttendanceScheduleContext
    {
        $start = is_string($startDate) ? Carbon::parse($startDate)->toDateString() : $startDate->toDateString();
        $end = is_string($endDate) ? Carbon::parse($endDate)->toDateString() : $endDate->toDateString();

        $userCollection = is_array($users) ? collect($users) : $users;
        $userIds = $userCollection->pluck('id')->filter()->toArray();
        $divisionIds = $userCollection->pluck('division_id')->filter()->unique()->toArray();

        // Preload WorkSchedules
        $workSchedules = WorkSchedule::whereIn('user_id', $userIds)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn ($item) => $item->user_id . '_' . $item->date->format('Y-m-d'));

        // Preload General Holidays
        $generalHolidays = Holiday::where('type', 'general')
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));

        // Preload Division Holidays
        $divisionHolidays = Holiday::where('type', 'division')
            ->whereIn('division_id', $divisionIds)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy(fn ($item) => $item->division_id . '_' . $item->date->format('Y-m-d'));

        // Preload Custom User Holidays
        $customHolidays = Holiday::where('type', 'custom')
            ->whereBetween('date', [$start, $end])
            ->with(['users' => function ($q) use ($userIds) {
                $q->whereIn('users.id', $userIds);
            }])
            ->get();

        $customHolidayMap = [];
        foreach ($customHolidays as $ch) {
            $dateStr = $ch->date->format('Y-m-d');
            foreach ($ch->users as $u) {
                $customHolidayMap[$u->id . '_' . $dateStr] = $ch;
            }
        }

        // Preload Divisions if not eager loaded
        $divisionsMap = Division::whereIn('id', $divisionIds)->get()->keyBy('id');

        return new AttendanceScheduleContext(
            workSchedules: $workSchedules,
            generalHolidays: $generalHolidays,
            divisionHolidays: $divisionHolidays,
            customHolidayMap: $customHolidayMap,
            divisionsMap: $divisionsMap
        );
    }
}

class AttendanceScheduleContext
{
    public function __construct(
        public Collection $workSchedules,
        public Collection $generalHolidays,
        public Collection $divisionHolidays,
        public array $customHolidayMap,
        public Collection $divisionsMap
    ) {}

    public function isWorkingDay(User $user, Carbon|string $date): bool
    {
        return $this->getScheduleDetails($user, $date)['is_working_day'];
    }

    public function getScheduleDetails(User $user, Carbon|string $date): array
    {
        $carbonDate = is_string($date) ? Carbon::parse($date) : $date->copy();
        $dateStr = $carbonDate->format('Y-m-d');
        $dayName = strtolower($carbonDate->format('l'));
        $userDateKey = $user->id . '_' . $dateStr;

        // Priority 1: WorkSchedule override
        if (isset($this->workSchedules[$userDateKey])) {
            $ws = $this->workSchedules[$userDateKey];
            return [
                'is_working_day' => (bool) $ws->is_working_day,
                'reason' => $ws->is_working_day ? 'Roster Work Day' : 'Roster Off Day',
                'type' => 'work_schedule',
            ];
        }

        // Priority 2: General Holiday
        if (isset($this->generalHolidays[$dateStr])) {
            $gh = $this->generalHolidays[$dateStr];
            return [
                'is_working_day' => false,
                'reason' => $gh->name,
                'type' => 'general_holiday',
            ];
        }

        // Priority 3: Division Holiday
        if ($user->division_id) {
            $divKey = $user->division_id . '_' . $dateStr;
            if (isset($this->divisionHolidays[$divKey]) && $this->divisionHolidays[$divKey]->isNotEmpty()) {
                $dh = $this->divisionHolidays[$divKey]->first();
                return [
                    'is_working_day' => false,
                    'reason' => $dh->name,
                    'type' => 'division_holiday',
                ];
            }
        }

        // Priority 4: Custom User Holiday
        if (isset($this->customHolidayMap[$userDateKey])) {
            $ch = $this->customHolidayMap[$userDateKey];
            return [
                'is_working_day' => false,
                'reason' => $ch->name,
                'type' => 'custom_holiday',
            ];
        }

        // Priority 5: User Recurring Off-Days
        $userOffDays = is_array($user->off_days) ? array_map('strtolower', $user->off_days) : null;
        if (!empty($userOffDays)) {
            if (in_array($dayName, $userOffDays, true)) {
                return [
                    'is_working_day' => false,
                    'reason' => 'User Off Day',
                    'type' => 'user_off_day',
                ];
            }
        } else {
            // Priority 6: Division Recurring Off-Days
            $division = $user->relationLoaded('division') ? $user->division : ($this->divisionsMap[$user->division_id] ?? null);
            $divisionOffDays = ($division && is_array($division->off_days)) ? array_map('strtolower', $division->off_days) : null;

            if (!empty($divisionOffDays)) {
                if (in_array($dayName, $divisionOffDays, true)) {
                    return [
                        'is_working_day' => false,
                        'reason' => 'Division Off Day',
                        'type' => 'division_off_day',
                    ];
                }
            } else {
                // Priority 7: Default Sunday Off-Day
                if ($carbonDate->isSunday()) {
                    return [
                        'is_working_day' => false,
                        'reason' => 'Default Sunday Off',
                        'type' => 'default_sunday_off',
                    ];
                }
            }
        }

        // Priority 8: Working Day
        return [
            'is_working_day' => true,
            'reason' => 'Regular Working Day',
            'type' => 'working_day',
        ];
    }
}
