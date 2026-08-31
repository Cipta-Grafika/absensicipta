<?php

namespace App\Livewire\User;

use App\Models\Saving;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SyirkahHistoryComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $month = '';
    public $type = '';
    public $perPage = 15;

    public $selectedTransaction = null;
    public $isDetailModalOpen = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'month' => ['except' => ''],
        'type' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMonth()
    {
        $this->resetPage();
    }

    public function updatingType()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'month', 'type']);
        $this->resetPage();
    }

    public function openDetailModal($id)
    {
        $this->selectedTransaction = SavingTransaction::with(['masterSaving', 'approver', 'user'])
            ->where('user_id', Auth::id())
            ->where('status', 'approved')
            ->find($id);

        if ($this->selectedTransaction) {
            $this->isDetailModalOpen = true;
        }
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedTransaction = null;
    }

    public function render()
    {
        $userId = Auth::id();

        // 1. Single Source of Truth: Total Approved Balances for current logged-in employee
        $approvedDepositQuery = SavingTransaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('transaction_type', 'deposit');

        $approvedWithdrawalQuery = SavingTransaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('transaction_type', 'withdrawal');

        $totalMandatoryDeposit = (float) $approvedDepositQuery->sum('mandatory_amount');
        $totalMandatoryWithdrawal = (float) $approvedWithdrawalQuery->sum('mandatory_amount');
        $saldoWajib = max(0.0, $totalMandatoryDeposit - $totalMandatoryWithdrawal);

        $totalSecondaryDeposit = (float) $approvedDepositQuery->sum('secondary_amount');
        $totalSecondaryWithdrawal = (float) $approvedWithdrawalQuery->sum('secondary_amount');
        $saldoSukarela = max(0.0, $totalSecondaryDeposit - $totalSecondaryWithdrawal);

        $totalSaldo = $saldoWajib + $saldoSukarela;
        $totalCreditAll = $totalMandatoryDeposit + $totalSecondaryDeposit;
        $totalDebitAll = $totalMandatoryWithdrawal + $totalSecondaryWithdrawal;

        // 2. Query transactions for ledger table (strictly approved only)
        $query = SavingTransaction::with(['masterSaving', 'approver'])
            ->where('user_id', $userId)
            ->where('status', 'approved');

        if (!empty($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_type', 'like', "%{$search}%")
                  ->orWhereHas('masterSaving', function ($subQ) use ($search) {
                      $subQ->where('savings_name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($this->month)) {
            try {
                $date = Carbon::parse($this->month);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {}
        }

        if (!empty($this->type)) {
            $query->where('transaction_type', $this->type);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.user.syirkah-history-component', [
            'transactions' => $transactions,
            'saldoWajib' => $saldoWajib,
            'saldoSukarela' => $saldoSukarela,
            'totalSaldo' => $totalSaldo,
            'totalCreditAll' => $totalCreditAll,
            'totalDebitAll' => $totalDebitAll,
            'totalTransactionsCount' => SavingTransaction::where('user_id', $userId)->where('status', 'approved')->count(),
        ])->layout('layouts.app');
    }
}
