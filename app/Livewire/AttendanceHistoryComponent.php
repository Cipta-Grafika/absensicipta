<?php

namespace App\Livewire;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class AttendanceHistoryComponent extends Component
{
    use AttendanceDetailTrait;

    public ?string $month = null;

    public function mount()
    {
        $this->month = date('Y-m');
    }

    public function render()
    {
        $user = auth()->user();
        $date = Carbon::parse($this->month);

        $start = Carbon::parse($this->month)->startOfMonth();
        $end = Carbon::parse($this->month)->endOfMonth();
        $dates = $start->range($end)->toArray();

        $attendances = new Collection(Cache::remember(
            "attendance-$user->id-$date->month-$date->year",
            now()->addDay(),
            function () use ($user) {
                /** @var Collection<Attendance>  */
                $attendances = Attendance::filter(
                    month: $this->month,
                    userId: $user->id,
                )->get(['id', 'status', 'date', 'latitude', 'longitude', 'attachment', 'note', 'imp_duration_hours', 'replaced_duration_hours']);

                return $attendances->map(
                    function (Attendance $v) {
                        $v->setAttribute('coordinates', $v->lat_lng);
                        $v->setAttribute('lat', $v->latitude);
                        $v->setAttribute('lng', $v->longitude);
                        if ($v->attachment) {
                            $v->setAttribute('attachment', $v->attachment_url);
                        }
                        return $v->getAttributes();
                    }
                )->toArray();
            }
        ) ?? []);
        $attendanceToday = $attendances->first(fn ($v, $_) => $v['date'] === Carbon::now()->format('Y-m-d'));

        // Calculate counts for current month
        $currentCounts = $this->calculateCounts($dates, $attendances->toArray());

        // Previous month calculation
        $prevMonth = Carbon::parse($this->month)->subMonth();
        $prevStart = $prevMonth->copy()->startOfMonth();
        $prevEnd = $prevMonth->copy()->endOfMonth();
        $prevDates = $prevStart->range($prevEnd)->toArray();

        $prevAttendances = Attendance::filter(month: $prevMonth->format('Y-m'), userId: $user->id)->get()->toArray();
        $prevCounts = $this->calculateCounts($prevDates, $prevAttendances);

        $stats = [
            'present' => ['current' => $currentCounts['present'] + $currentCounts['late'], 'last' => $prevCounts['present'] + $prevCounts['late']],
            'excused' => ['current' => $currentCounts['excused'], 'last' => $prevCounts['excused']],
            'sick'    => ['current' => $currentCounts['sick'], 'last' => $prevCounts['sick']],
            'wfh'     => ['current' => $currentCounts['wfh'], 'last' => $prevCounts['wfh']],
            'leave'   => ['current' => $currentCounts['leave'] + $currentCounts['special-leaves'], 'last' => $prevCounts['leave'] + $prevCounts['special-leaves']],
            'absent'  => ['current' => $currentCounts['absent'], 'last' => $prevCounts['absent']],
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

        $sparklines = $this->getSparklines($start, $end, $user);

        return view('livewire.attendance-history', [
            'attendances' => $attendances,
            'attendanceToday' => $attendanceToday,
            'dates' => $dates,
            'start' => $start,
            'end' => $end,
            'stats' => $stats,
            'sparklines' => $sparklines,
            'presentCount' => $currentCounts['present'] + $currentCounts['late'],
            'wfhCount' => $currentCounts['wfh'],
            'excusedCount' => $currentCounts['excused'],
            'sickCount' => $currentCounts['sick'],
            'leaveCount' => $currentCounts['leave'] + $currentCounts['special-leaves'],
            'absentCount' => $currentCounts['absent'],
        ]);
    }

    private function calculateCounts($dates, $attendances)
    {
        $counts = [
            'present' => 0, 'late' => 0, 'excused' => 0, 'sick' => 0, 'absent' => 0, 'wfh' => 0, 'leave' => 0, 'special-leaves' => 0,
        ];

        foreach ($dates as $date) {
            $isSunday = $date->isSunday();
            $attendance = collect($attendances)->firstWhere('date', $date->format('Y-m-d'));
            $status = ($attendance ?? ['status' => $isSunday || !$date->isPast() ? '-' : 'absent'])['status'];
            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }
        return $counts;
    }

    private function getSparklines($start, $end, $user)
    {
        $query = Attendance::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('user_id', $user->id)
            ->selectRaw('date, status, count(*) as count')
            ->groupBy('date', 'status')
            ->get();

        $periods = [];
        $current = $start->copy();
        while ($current <= $end) {
            $periods[$current->format('Y-m-d')] = [
                'present' => 0, 'late' => 0, 'excused' => 0, 'sick' => 0, 'wfh' => 0, 'leave' => 0, 'absent' => 0, 'special-leaves' => 0
            ];
            if (!$current->isSunday() && $current->isPast()) {
                $periods[$current->format('Y-m-d')]['absent'] = 1;
            }
            $current->addDay();
        }

        foreach ($query as $r) {
            $p = Carbon::parse($r->date)->format('Y-m-d');
            if (isset($periods[$p])) {
                $periods[$p][$r->status] = $r->count;
                $periods[$p]['absent'] = 0;
            }
        }

        $sparklines = [];
        $statuses = [
            'present' => ['present', 'late'], 
            'excused' => ['excused'], 
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
            $y = $max == $min ? 10 : $height - (($val - $min) / ($max - $min)) * ($height - 4) - 2;
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
