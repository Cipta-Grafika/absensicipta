<?php

namespace App\Livewire\Payroll;

use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class PayrollDashboardComponent extends Component
{
    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);

        $currentMonth = date('Y-m');
        
        $totalEmployees = User::where('group', 'user')->count();
        
        $payrollsThisMonth = Payroll::where('period_month', $currentMonth)->get();
        
        $totalPaidOut = $payrollsThisMonth->where('status', 'paid')->sum('net_salary');
        $totalDraft = $payrollsThisMonth->where('status', 'draft')->sum('net_salary');
        
        $paidCount = $payrollsThisMonth->where('status', 'paid')->count();
        $draftCount = $payrollsThisMonth->where('status', 'draft')->count();

        return view('livewire.payroll.payroll-dashboard-component', [
            'totalEmployees' => $totalEmployees,
            'totalPaidOut' => $totalPaidOut,
            'totalDraft' => $totalDraft,
            'paidCount' => $paidCount,
            'draftCount' => $draftCount,
            'currentMonth' => Carbon::parse($currentMonth)->format('F Y'),
        ])->layout('layouts.app');
    }
}
