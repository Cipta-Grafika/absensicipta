<?php

namespace App\Livewire\Payroll;

use App\Models\Payroll;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Services\SavingTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PayrollDashboardComponent extends Component
{
    public $month = '';
    public $bulkApproveModalOpen = false;
    public $bulkApproveScope = 'current_month'; // 'current_month' or 'all_pending'

    public function openBulkApproveModal(string $scope = 'current_month'): void
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isOwner && !$user->isPayroll && !$user->isSyirkah)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui mutasi syirkah.');
        }

        $this->bulkApproveScope = in_array($scope, ['current_month', 'all_pending'], true) ? $scope : 'current_month';
        $this->bulkApproveModalOpen = true;
    }

    public function closeBulkApproveModal(): void
    {
        $this->bulkApproveModalOpen = false;
    }

    public function confirmBulkApprove(): void
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isOwner && !$user->isPayroll && !$user->isSyirkah)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui mutasi syirkah.');
        }

        $currentMonth = $this->month ?: date('Y-m');

        $query = SavingTransaction::where('status', 'pending');

        if ($this->bulkApproveScope === 'current_month') {
            $payrollIds = Payroll::where('period_month', $currentMonth)->pluck('id');
            $query->where(function ($q) use ($payrollIds, $currentMonth) {
                $q->where('reference_type', 'payroll')
                  ->where(function ($sub) use ($payrollIds, $currentMonth) {
                      if ($payrollIds->isNotEmpty()) {
                          $sub->whereIn('reference_id', $payrollIds);
                      }
                      $sub->orWhere('description', 'like', '%Payroll ' . $currentMonth . '%');
                  });
            });
        }

        $transactionIds = $query->pluck('id')->map(fn($id) => (string)$id)->toArray();

        if (empty($transactionIds)) {
            $this->dispatch('notify', 'Tidak ada mutasi syirkah pending yang perlu disetujui.');
            $this->bulkApproveModalOpen = false;
            return;
        }

        $count = SavingTransactionService::bulkApprove($transactionIds, (string) $user->id);

        $this->bulkApproveModalOpen = false;
        $this->dispatch('notify', "Sebanyak {$count} transaksi mutasi syirkah berhasil disetujui & saldo berhasil diperbarui.");
    }

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

        // Aggregated Syirkah Metrics for Current Payroll Period
        $payrollIdsThisMonth = $payrollsThisMonth->pluck('id');
        $syirkahPayrollQuery = SavingTransaction::where(function ($q) use ($payrollIdsThisMonth, $currentMonth) {
            $q->where('reference_type', 'payroll')
              ->where(function ($sub) use ($payrollIdsThisMonth, $currentMonth) {
                  if ($payrollIdsThisMonth->isNotEmpty()) {
                      $sub->whereIn('reference_id', $payrollIdsThisMonth);
                  }
                  $sub->orWhere('description', 'like', '%Payroll ' . $currentMonth . '%');
              });
        });

        $syirkahRawStats = (clone $syirkahPayrollQuery)->selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(mandatory_amount) as total_mandatory,
            SUM(secondary_amount) as total_secondary,
            SUM(mandatory_amount + secondary_amount) as total_amount,
            SUM(CASE WHEN status = 'pending' THEN mandatory_amount ELSE 0 END) as pending_mandatory,
            SUM(CASE WHEN status = 'pending' THEN secondary_amount ELSE 0 END) as pending_secondary,
            SUM(CASE WHEN status = 'pending' THEN (mandatory_amount + secondary_amount) ELSE 0 END) as pending_total,
            SUM(CASE WHEN status = 'approved' THEN (mandatory_amount + secondary_amount) ELSE 0 END) as approved_total
        ")->first();

        $syirkahSummary = [
            'total_count' => (int) ($syirkahRawStats->total_count ?? 0),
            'pending_count' => (int) ($syirkahRawStats->pending_count ?? 0),
            'approved_count' => (int) ($syirkahRawStats->approved_count ?? 0),
            'rejected_count' => (int) ($syirkahRawStats->rejected_count ?? 0),
            'total_mandatory' => (float) ($syirkahRawStats->total_mandatory ?? 0),
            'total_secondary' => (float) ($syirkahRawStats->total_secondary ?? 0),
            'total_amount' => (float) ($syirkahRawStats->total_amount ?? 0),
            'pending_mandatory' => (float) ($syirkahRawStats->pending_mandatory ?? 0),
            'pending_secondary' => (float) ($syirkahRawStats->pending_secondary ?? 0),
            'pending_total' => (float) ($syirkahRawStats->pending_total ?? 0),
            'approved_total' => (float) ($syirkahRawStats->approved_total ?? 0),
        ];

        // Global Pending Syirkah summary across system
        $allPendingRaw = SavingTransaction::where('status', 'pending')
            ->selectRaw("
                COUNT(*) as count,
                SUM(mandatory_amount) as total_mandatory,
                SUM(secondary_amount) as total_secondary,
                SUM(mandatory_amount + secondary_amount) as total_amount
            ")->first();

        $allPendingSummary = [
            'count' => (int) ($allPendingRaw->count ?? 0),
            'total_mandatory' => (float) ($allPendingRaw->total_mandatory ?? 0),
            'total_secondary' => (float) ($allPendingRaw->total_secondary ?? 0),
            'total_amount' => (float) ($allPendingRaw->total_amount ?? 0),
        ];

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
            'syirkahSummary' => $syirkahSummary,
            'allPendingSummary' => $allPendingSummary,
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
