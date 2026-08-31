<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Division;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\ErrorDeduction;
use Laravel\Jetstream\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ErrorDeductionComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    // Filters
    public $search = '';
    public $division = '';
    public $status = '';
    public $deduction_source = '';
    public $selected_period_month = '';

    // Modal Add / Edit
    public $isModalOpen = false;
    public $editingId = null;
    public $user_id = '';
    public $period_month = '';
    public $error_date = '';
    public $error_title = '';
    public $description = '';
    public $total_error_cost = 0;
    public $amount = 0;
    public $form_deduction_source = 'payroll';
    public $form_status = 'pending';

    // Realtime Syirkah balance preview for selected employee
    public $employee_syirkah_mandatory = 0;
    public $employee_syirkah_secondary = 0;

    // Modal Delete
    public $isDeleteModalOpen = false;
    public $deletingId = null;

    protected function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'period_month' => 'required|date_format:Y-m',
            'error_date' => 'required|date',
            'error_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_error_cost' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'form_deduction_source' => 'required|in:payroll,syirkah_mandatory,syirkah_secondary,syirkah_all',
            'form_status' => 'required|in:pending,approved,processed,cancelled',
        ];
    }

    public function mount()
    {
        $this->selected_period_month = now()->format('Y-m');
        $this->period_month = now()->format('Y-m');
        $this->error_date = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDivision()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDeductionSource()
    {
        $this->resetPage();
    }

    public function updatingSelectedPeriodMonth()
    {
        $this->resetPage();
    }

    public function updatedUserId($value)
    {
        $this->loadEmployeeSyirkahBalance($value);
    }

    public function loadEmployeeSyirkahBalance($userId)
    {
        if (!$userId) {
            $this->employee_syirkah_mandatory = 0;
            $this->employee_syirkah_secondary = 0;
            return;
        }

        $depositMandatory = (float) SavingTransaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('transaction_type', 'deposit')
            ->sum('mandatory_amount');
        $withdrawalMandatory = (float) SavingTransaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('transaction_type', 'withdrawal')
            ->sum('mandatory_amount');

        $depositSecondary = (float) SavingTransaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('transaction_type', 'deposit')
            ->sum('secondary_amount');
        $withdrawalSecondary = (float) SavingTransaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('transaction_type', 'withdrawal')
            ->sum('secondary_amount');

        $this->employee_syirkah_mandatory = max(0, $depositMandatory - $withdrawalMandatory);
        $this->employee_syirkah_secondary = max(0, $depositSecondary - $withdrawalSecondary);
    }

    #[\Livewire\Attributes\On('open-create-modal')]
    public function openCreateModal()
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->user_id = '';
        $this->period_month = $this->selected_period_month ?: now()->format('Y-m');
        $this->error_date = now()->format('Y-m-d');
        $this->error_title = '';
        $this->description = '';
        $this->total_error_cost = 0;
        $this->amount = 0;
        $this->form_deduction_source = 'payroll';
        $this->form_status = 'pending';
        $this->employee_syirkah_mandatory = 0;
        $this->employee_syirkah_secondary = 0;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $error = ErrorDeduction::findOrFail($id);

        $this->editingId = $error->id;
        $this->user_id = $error->user_id;
        $this->period_month = $error->period_month;
        $this->error_date = $error->error_date ? $error->error_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->error_title = $error->error_title;
        $this->description = $error->description;
        $this->total_error_cost = (float) $error->total_error_cost;
        $this->amount = (float) $error->amount;
        $this->form_deduction_source = $error->deduction_source;
        $this->form_status = $error->status;

        $this->loadEmployeeSyirkahBalance($error->user_id);
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $error = ErrorDeduction::findOrFail($this->editingId);
            $error->update([
                'user_id' => $this->user_id,
                'period_month' => $this->period_month,
                'error_date' => $this->error_date,
                'error_title' => $this->error_title,
                'description' => $this->description,
                'total_error_cost' => $this->total_error_cost ?: 0,
                'amount' => $this->amount,
                'deduction_source' => $this->form_deduction_source,
                'status' => $this->form_status,
            ]);
            $this->banner(__('Data log potongan error karyawan berhasil diperbarui.'));
        } else {
            ErrorDeduction::create([
                'user_id' => $this->user_id,
                'period_month' => $this->period_month,
                'error_date' => $this->error_date,
                'error_title' => $this->error_title,
                'description' => $this->description,
                'total_error_cost' => $this->total_error_cost ?: 0,
                'amount' => $this->amount,
                'deduction_source' => $this->form_deduction_source,
                'status' => $this->form_status,
                'created_by' => Auth::id(),
            ]);
            $this->banner(__('Log potongan error karyawan berhasil dicatat.'));
        }

        $this->isModalOpen = false;
        $this->editingId = null;
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if ($this->deletingId) {
            $error = ErrorDeduction::findOrFail($this->deletingId);

            // If it created a saving transaction, delete it
            if ($error->saving_transaction_id) {
                $st = SavingTransaction::find($error->saving_transaction_id);
                if ($st) {
                    $userId = $st->user_id;
                    $st->delete();
                    \App\Services\SavingTransactionService::recalculateUserTransactions($userId);
                }
            }

            $error->delete();
            $this->banner(__('Log potongan error berhasil dihapus.'));
            $this->isDeleteModalOpen = false;
            $this->deletingId = null;
        }
    }

    public function updateStatus($id, $newStatus)
    {
        $error = ErrorDeduction::findOrFail($id);
        $error->update(['status' => $newStatus]);
        $this->banner(__('Status log potongan error berhasil diubah menjadi: ') . ucfirst($newStatus));
    }

    public function render()
    {
        $employees = User::onlyWorkingEmployee()
            ->with('division')
            ->orderBy('name')
            ->get();

        $divisions = Division::orderBy('name')->get();

        $query = ErrorDeduction::with(['user.division', 'creator', 'payroll', 'savingTransaction'])
            ->when($this->selected_period_month, fn($q) => $q->where('period_month', $this->selected_period_month))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->deduction_source, fn($q) => $q->where('deduction_source', $this->deduction_source))
            ->when($this->division, function ($q) {
                $q->whereHas('user', fn($sub) => $sub->where('division_id', $this->division));
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('error_title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($u) {
                            $u->where('name', 'like', '%' . $this->search . '%')
                              ->orWhere('nip', 'like', '%' . $this->search . '%');
                        });
                });
            });

        // Summary metrics
        $allStatsQuery = ErrorDeduction::when($this->selected_period_month, fn($q) => $q->where('period_month', $this->selected_period_month));
        $totalCases = (clone $allStatsQuery)->count();
        $totalErrorCost = (clone $allStatsQuery)->sum('total_error_cost');
        $totalDeductionAmount = (clone $allStatsQuery)->sum('amount');
        $totalProcessedAmount = (clone $allStatsQuery)->where('status', 'processed')->sum('amount');

        $errorDeductions = $query->orderBy('error_date', 'desc')->paginate(10);

        return view('livewire.payroll.error-deduction-component', [
            'errorDeductions' => $errorDeductions,
            'employees' => $employees,
            'divisions' => $divisions,
            'totalCases' => $totalCases,
            'totalErrorCost' => $totalErrorCost,
            'totalDeductionAmount' => $totalDeductionAmount,
            'totalProcessedAmount' => $totalProcessedAmount,
        ])->layout('layouts.app', ['header' => __('Master Log Potongan Error Produksi')]);
    }
}
