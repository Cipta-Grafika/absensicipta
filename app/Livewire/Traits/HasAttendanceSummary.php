<?php

namespace App\Livewire\Traits;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasAttendanceSummary
{
    public function getAttendanceSummary($date, $week, $month, $search = null, $division = null, $jobTitle = null)
    {
        $queryDateStart = now()->startOfDay();
        $queryDateEnd = now()->endOfDay();
        $prevDateStart = now()->subDay()->startOfDay();
        $prevDateEnd = now()->subDay()->endOfDay();
        $filterText = 'vs hari lalu';
        $titlePrefix = 'Hari Ini';
        $isTodayOnly = true;

        if ($date) {
            $parsedDate = Carbon::parse($date);
            $queryDateStart = $parsedDate->copy()->startOfDay();
            $queryDateEnd = $parsedDate->copy()->endOfDay();
            $prevDateStart = $parsedDate->copy()->subDay()->startOfDay();
            $prevDateEnd = $parsedDate->copy()->subDay()->endOfDay();
            $filterText = 'vs hari lalu';
            $titlePrefix = $parsedDate->format('d M Y');
            $isTodayOnly = $parsedDate->isToday();
        } elseif ($week) {
            $parsedWeek = Carbon::parse($week);
            $queryDateStart = $parsedWeek->copy()->startOfWeek();
            $queryDateEnd = $parsedWeek->copy()->endOfWeek();
            $prevDateStart = $parsedWeek->copy()->subWeek()->startOfWeek();
            $prevDateEnd = $parsedWeek->copy()->subWeek()->endOfWeek();
            $filterText = 'vs mgg lalu';
            $titlePrefix = 'Minggu Ini';
            $isTodayOnly = false;
        } elseif ($month) {
            $parsedMonth = Carbon::parse($month);
            $queryDateStart = $parsedMonth->copy()->startOfMonth();
            $queryDateEnd = $parsedMonth->copy()->endOfMonth();
            $prevDateStart = $parsedMonth->copy()->subMonth()->startOfMonth();
            $prevDateEnd = $parsedMonth->copy()->subMonth()->endOfMonth();
            $filterText = 'vs bln lalu';
            $titlePrefix = 'Bulan ' . $parsedMonth->translatedFormat('F Y');
            $isTodayOnly = false;
        }

        $user = auth()->user();

        $userFilter = function (Builder $q) use ($user, $search, $division, $jobTitle) {
            if ($user->group === 'admin') {
                $q->where('division_id', $user->division_id);
            }
            if ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('nip', 'like', '%' . $search . '%');
                });
            }
            if ($division) {
                $q->where('division_id', $division);
            }
            if ($jobTitle) {
                $q->where('job_title_id', $jobTitle);
            }
        };

        // Fetch current period attendances
        $currentAttendances = Attendance::whereBetween('date', [$queryDateStart->format('Y-m-d'), $queryDateEnd->format('Y-m-d')])
            ->whereHas('user', $userFilter)
            ->get();

        // Fetch last period attendances
        $lastAttendances = Attendance::whereBetween('date', [$prevDateStart->format('Y-m-d'), $prevDateEnd->format('Y-m-d')])
            ->whereHas('user', $userFilter)
            ->get();

        $employeesCount = User::where('group', 'user')
            ->where($userFilter)
            ->count();

        $presentCount = $currentAttendances->whereIn('status', ['present', 'late'])->count();
        $lateCount = $currentAttendances->where('status', 'late')->count();
        $excusedCount = $currentAttendances->whereIn('status', ['excused', 'imp'])->count();
        $sickCount = $currentAttendances->where('status', 'sick')->count();
        $wfhCount = $currentAttendances->where('status', 'wfh')->count();
        $leaveCount = $currentAttendances->where('status', 'leave')->count();
        $specialLeaveCount = $currentAttendances->where('status', 'special-leaves')->count();

        if ($isTodayOnly) {
            $absentCount = $employeesCount - ($presentCount + $excusedCount + $sickCount + $wfhCount + $leaveCount + $specialLeaveCount);
        } else {
            $absentCount = $currentAttendances->where('status', 'absent')->count();
        }

        $prevPresentCount = $lastAttendances->whereIn('status', ['present', 'late'])->count();
        $prevExcusedCount = $lastAttendances->whereIn('status', ['excused', 'imp'])->count();
        $prevSickCount = $lastAttendances->where('status', 'sick')->count();
        $prevWfhCount = $lastAttendances->where('status', 'wfh')->count();
        $prevLeaveCount = $lastAttendances->where('status', 'leave')->count();
        $prevSpecialLeaveCount = $lastAttendances->where('status', 'special-leaves')->count();
        $prevAbsentCount = $lastAttendances->where('status', 'absent')->count();

        $stats = [
            'present' => ['current' => $presentCount, 'last' => $prevPresentCount],
            'excused' => ['current' => $excusedCount, 'last' => $prevExcusedCount],
            'sick'    => ['current' => $sickCount, 'last' => $prevSickCount],
            'wfh'     => ['current' => $wfhCount, 'last' => $prevWfhCount],
            'leave'   => ['current' => $leaveCount + $specialLeaveCount, 'last' => $prevLeaveCount + $prevSpecialLeaveCount],
            'absent'  => ['current' => $absentCount, 'last' => $prevAbsentCount],
        ];

        foreach ($stats as $key => $val) {
            $diff = $val['current'] - $val['last'];
            
            if ($val['last'] > 0) {
                $pct = round(($diff / $val['last']) * 100);
                $stats[$key]['trend'] = $diff > 0 ? "+$pct%" : "$pct%";
            } else {
                $stats[$key]['trend'] = $diff > 0 ? "+$diff" : (string)$diff;
            }
            
            $stats[$key]['is_up'] = $diff > 0;
            $stats[$key]['is_same'] = $diff == 0;
            $stats[$key]['is_down'] = $diff < 0;
        }

        $sparklines = $this->generateDynamicSparklines($date, $week, $month, $user, $userFilter);

        return [
            'employeesCount' => $employeesCount,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'excusedCount' => $excusedCount,
            'sickCount' => $sickCount,
            'wfhCount' => $wfhCount,
            'leaveCount' => $leaveCount + $specialLeaveCount,
            'absentCount' => $absentCount,
            'stats' => $stats,
            'filterText' => $filterText,
            'titlePrefix' => $titlePrefix,
            'sparklines' => $sparklines,
            'currentAttendances' => $currentAttendances,
        ];
    }

    private function generateDynamicSparklines($date, $week, $month, $user, $userFilter = null)
    {
        $points = 10; // Number of points to show in sparkline
        
        if ($date) {
            $end = Carbon::parse($date)->endOfDay();
            $start = $end->copy()->subDays($points - 1)->startOfDay();
            $format = 'Y-m-d';
            $step = 'addDay';
        } elseif ($week) {
            $end = Carbon::parse($week)->endOfWeek();
            $start = $end->copy()->subWeeks($points - 1)->startOfWeek();
            $format = 'W-Y';
            $step = 'addWeek';
        } else {
            $end = $month ? Carbon::parse($month)->endOfMonth() : now()->endOfMonth();
            $start = $end->copy()->subMonths($points - 1)->startOfMonth();
            $format = 'Y-m';
            $step = 'addMonth';
        }

        $query = Attendance::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
        
        if ($userFilter) {
            $query->whereHas('user', $userFilter);
        }

        $records = $query->selectRaw('date, status, count(*) as count')
            ->groupBy('date', 'status')
            ->get();

        $periods = [];
        $current = $start->copy();
        while ($current <= $end) {
            $periods[$current->format($format)] = [
                'present' => 0, 'late' => 0, 'excused' => 0, 'sick' => 0, 'wfh' => 0, 'leave' => 0, 'absent' => 0, 'imp' => 0, 'special-leaves' => 0
            ];
            $current->$step();
        }

        foreach ($records as $r) {
            $p = Carbon::parse($r->date)->format($format);
            if (isset($periods[$p])) {
                $periods[$p][$r->status] += $r->count;
            }
        }

        $sparklines = [];
        $statuses = [
            'present' => ['present', 'late'], 
            'excused' => ['excused', 'imp'], 
            'sick' => ['sick'], 
            'wfh' => ['wfh'], 
            'leave' => ['leave', 'special-leaves'], 
            'absent' => ['absent']
        ];
        
        foreach ($statuses as $key => $statusGroup) {
            $values = [];
            foreach ($periods as $p => $counts) {
                $sum = 0;
                foreach ($statusGroup as $s) {
                    $sum += $counts[$s];
                }
                $values[] = $sum;
            }
            $sparklines[$key] = $this->makeSvgPath($values);
        }
        return $sparklines;
    }

    private function makeSvgPath($values)
    {
        $min = min($values);
        $max = max($values);
        
        $width = 100;
        $height = 20;
        
        if ($max == 0) {
            return [
                'stroke' => 'M0 15 L100 15',
                'fill' => 'M0 20 L0 15 L100 15 L100 20 Z'
            ];
        }
        
        $stepX = count($values) > 1 ? $width / (count($values) - 1) : $width;
        
        $strokePath = [];
        $fillPath = ["M0 20"];
        
        foreach ($values as $i => $val) {
            $x = $i * $stepX;
            if ($max == $min) {
                $y = 10;
            } else {
                $y = $height - (($val - $min) / ($max - $min)) * ($height - 4) - 2;
            }
            $prefix = $i === 0 ? 'M' : 'L';
            $strokePath[] = "$prefix" . round($x, 1) . " " . round($y, 1);
            $fillPath[] = "L" . round($x, 1) . " " . round($y, 1);
        }
        
        $fillPath[] = "L100 20 Z";
        
        return [
            'stroke' => implode(' ', $strokePath),
            'fill' => implode(' ', $fillPath),
        ];
    }
}
