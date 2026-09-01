<?php

namespace App\Livewire\Payroll;

use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class PayrollDashboardComponent extends Component
{
    public $month = '';

    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin || auth()->user()->isOwner, 403);

        $currentMonth = $this->month ?: date('Y-m');
        $date = Carbon::createFromFormat('Y-m', $currentMonth);
        $prevMonthDate = $date->copy()->subMonth();
        $prevMonth = $prevMonthDate->format('Y-m');
        
        $totalEmployees = User::where('group', 'user')->where('created_at', '<=', $date->copy()->endOfMonth())->count();
        $payrollsThisMonth = Payroll::where('period_month', $currentMonth)->get();
        
        $totalPaidOut = $payrollsThisMonth->where('status', 'paid')->sum('net_salary');
        $totalDraft = $payrollsThisMonth->where('status', 'draft')->sum('net_salary');
        
        $paidCount = $payrollsThisMonth->where('status', 'paid')->count();
        $draftCount = $payrollsThisMonth->where('status', 'draft')->count();

        $prevTotalEmployees = User::where('group', 'user')->where('created_at', '<=', $prevMonthDate->copy()->endOfMonth())->count();
        $payrollsPrevMonth = Payroll::where('period_month', $prevMonth)->get();
        $prevTotalPaidOut = $payrollsPrevMonth->where('status', 'paid')->sum('net_salary');
        $prevTotalDraft = $payrollsPrevMonth->where('status', 'draft')->sum('net_salary');

        $stats = [
            'employees' => $this->calculateTrend($totalEmployees, $prevTotalEmployees, true),
            'paid' => $this->calculateTrend($totalPaidOut, $prevTotalPaidOut),
            'draft' => $this->calculateTrend($totalDraft, $prevTotalDraft),
        ];

        $sparklines = $this->generateDynamicSparklines($date);

        return view('livewire.payroll.payroll-dashboard-component', [
            'totalEmployees' => $totalEmployees,
            'totalPaidOut' => $totalPaidOut,
            'totalDraft' => $totalDraft,
            'paidCount' => $paidCount,
            'draftCount' => $draftCount,
            'stats' => $stats,
            'sparklines' => $sparklines,
            'currentMonth' => Carbon::parse($currentMonth)->format('F Y'),
        ])->layout('layouts.app');
    }

    private function calculateTrend($current, $previous, $isCount = false)
    {
        if ($previous == 0) {
            $percent = $current > 0 ? 100 : 0;
            $diff = $current;
        } else {
            $diff = $current - $previous;
            $percent = ($diff / $previous) * 100;
        }

        $trend = $isCount ? ($diff > 0 ? '+'.$diff : $diff) : ($percent > 0 ? '+' . round($percent) . '%' : round($percent) . '%');

        return [
            'value' => $current,
            'is_up' => $diff > 0,
            'is_down' => $diff < 0,
            'trend' => $trend
        ];
    }

    private function generateDynamicSparklines($currentDate)
    {
        $points = 10;
        $sparklines = [];
        $values = ['employees' => [], 'paid' => [], 'draft' => []];

        for ($i = $points - 1; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i);
            $monthStr = $date->format('Y-m');

            $values['employees'][] = User::where('group', 'user')->where('created_at', '<=', $date->copy()->endOfMonth())->count();
            
            $payrolls = Payroll::where('period_month', $monthStr)->get();
            $values['paid'][] = $payrolls->where('status', 'paid')->sum('net_salary');
            $values['draft'][] = $payrolls->where('status', 'draft')->sum('net_salary');
        }

        foreach ($values as $key => $vals) {
            $sparklines[$key] = $this->makeSvgPath($vals);
        }

        return $sparklines;
    }

    private function makeSvgPath($values)
    {
        $min = min($values);
        $max = max($values);
        
        $width = 100;
        $height = 20;
        
        if ($max == 0 && $min == 0) {
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
            'fill' => implode(' ', $fillPath)
        ];
    }
}
