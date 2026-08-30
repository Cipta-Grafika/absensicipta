<?php

namespace App\Livewire\Payroll;

use App\Exports\BcaMatPayrollExport;
use App\Models\Division;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;

class ExportBankTransferComponent extends Component
{
    use InteractsWithBanner;

    public string $month = '';
    public string $transaction_date = '';
    public string $bank_type = 'BCA';
    public string $remark = '';
    public bool $only_with_account = false;
    public ?int $division_id = null;
    public string $status_filter = '';

    public array $selected_payrolls = [];
    public bool $select_all = true;

    public function mount()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSuperadmin, 403);

        $latestMonth = Payroll::orderBy('period_month', 'desc')->value('period_month') ?: date('Y-m');
        $this->month = request()->query('month', $latestMonth);
        $this->transaction_date = date('Y-m-d');
        
        try {
            $parsed = Carbon::parse($this->month . '-01');
            $this->remark = 'Gaji ' . $parsed->translatedFormat('M Y');
        } catch (\Exception $e) {
            $this->remark = 'Gaji ' . date('M Y');
        }

        $this->loadPayrollSelection();
    }

    public function updatedMonth()
    {
        try {
            $parsed = Carbon::parse($this->month . '-01');
            $this->remark = 'Gaji ' . $parsed->translatedFormat('M Y');
        } catch (\Exception $e) {}

        $this->loadPayrollSelection();
    }

    public function updatedOnlyWithAccount()
    {
        $this->loadPayrollSelection();
    }

    public function updatedDivisionId()
    {
        $this->loadPayrollSelection();
    }

    public function updatedStatusFilter()
    {
        $this->loadPayrollSelection();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->loadPayrollSelection();
        } else {
            $this->selected_payrolls = [];
        }
    }

    public function loadPayrollSelection()
    {
        $query = Payroll::with(['employee.paymentMethod', 'employee.division', 'employee.jobTitle'])
            ->whereHas('employee', function ($q) {
                $q->onlyEmployee();
            })
            ->where('period_month', $this->month)
            ->where('net_salary', '>', 0);

        if ($this->division_id) {
            $query->whereHas('employee', function ($q) {
                $q->where('division_id', $this->division_id);
            });
        }

        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        $payrolls = $query->join('users', 'payrolls.employee_id', '=', 'users.id')
            ->select('payrolls.*')
            ->orderBy('users.created_at', 'asc')
            ->orderBy('users.id', 'asc')
            ->get();

        if ($this->only_with_account) {
            $payrolls = $payrolls->filter(function ($p) {
                return !empty($p->employee?->paymentMethod?->bank_account);
            });
        }

        $this->selected_payrolls = $payrolls->pluck('id')->toArray();
    }

    public function export()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSuperadmin, 403);

        $this->validate([
            'month' => 'required|date_format:Y-m',
            'transaction_date' => 'required|date',
            'bank_type' => 'required|string',
            'remark' => 'nullable|string|max:18',
        ]);

        if (empty($this->selected_payrolls)) {
            $this->loadPayrollSelection();
        }

        if (empty($this->selected_payrolls)) {
            $this->dangerBanner(__('Tidak ada data payroll pada periode ini untuk diekspor. Pastikan payroll pada periode tersebut sudah diproses.'));
            return;
        }

        $export = new BcaMatPayrollExport(
            $this->month,
            $this->transaction_date,
            $this->bank_type,
            $this->remark,
            $this->selected_payrolls,
            $this->only_with_account
        );

        $dateStr = Carbon::parse($this->transaction_date)->format('dmY');
        $filename = sprintf('PAYROLL-%s_%s_%s.xlsx', strtoupper($this->bank_type), $this->month, $dateStr);

        return $export->download($filename);
    }

    public function render()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSuperadmin, 403);

        $query = Payroll::with(['employee.paymentMethod', 'employee.division', 'employee.jobTitle'])
            ->whereHas('employee', function ($q) {
                $q->onlyEmployee();
            })
            ->where('period_month', $this->month)
            ->where('net_salary', '>', 0);

        if ($this->division_id) {
            $query->whereHas('employee', function ($q) {
                $q->where('division_id', $this->division_id);
            });
        }

        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        $payrolls = $query->join('users', 'payrolls.employee_id', '=', 'users.id')
            ->select('payrolls.*')
            ->orderBy('users.created_at', 'asc')
            ->orderBy('users.id', 'asc')
            ->get();

        if ($this->only_with_account) {
            $payrolls = $payrolls->filter(function ($p) {
                return !empty($p->employee?->paymentMethod?->bank_account);
            });
        }

        $totalEmployees = $payrolls->count();
        $selectedCount = count(array_intersect($this->selected_payrolls, $payrolls->pluck('id')->toArray()));
        $selectedPayrollsModels = $payrolls->whereIn('id', $this->selected_payrolls);
        $totalTransferAmount = $selectedPayrollsModels->sum('net_salary');
        $missingAccountCount = $payrolls->filter(function ($p) {
            return empty($p->employee?->paymentMethod?->bank_account);
        })->count();

        $formattedDatePrefix = Carbon::parse($this->transaction_date ?: date('Y-m-d'))->format('dmY');

        return view('livewire.payroll.export-bank-transfer-component', [
            'payrolls' => $payrolls,
            'divisions' => Division::orderBy('name')->get(),
            'totalEmployees' => $totalEmployees,
            'selectedCount' => $selectedCount,
            'totalTransferAmount' => $totalTransferAmount,
            'missingAccountCount' => $missingAccountCount,
            'formattedDatePrefix' => $formattedDatePrefix,
            'month' => $this->month,
            'transaction_date' => $this->transaction_date,
            'bank_type' => $this->bank_type,
            'remark' => $this->remark,
            'only_with_account' => $this->only_with_account,
            'division_id' => $this->division_id,
            'status_filter' => $this->status_filter,
            'selected_payrolls' => $this->selected_payrolls,
            'select_all' => $this->select_all,
        ])->layout('layouts.app');
    }
}
