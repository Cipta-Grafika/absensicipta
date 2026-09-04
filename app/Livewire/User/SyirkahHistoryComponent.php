<?php

namespace App\Livewire\User;

use App\Models\EmployeeSalary;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Services\SavingTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SyirkahHistoryComponent extends Component
{
    use WithPagination;

    // Filters for Mutation Ledger
    public $search = '';
    public $month = '';
    public $type = '';
    public $perPage = 15;

    // Detail Modal State
    public $selectedTransaction = null;
    public $isDetailModalOpen = false;

    // Withdrawal Modal State
    public $isWithdrawalModalOpen = false;
    public $withdrawalType = 'full'; // 'full', 'mandatory', 'secondary'
    public $mandatoryAmount = 0;
    public $secondaryAmount = 0;
    public $savingsId = null;
    public $reason = '';

    // Detail Withdrawal Modal State
    public $selectedWithdrawal = null;
    public $isWithdrawalDetailModalOpen = false;

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

    public function openWithdrawalModal()
    {
        $this->resetErrorBag();
        $userId = Auth::id();

        // Get default savings program
        $this->savingsId = EmployeeSalary::where('employee_id', $userId)->value('savings_id')
            ?? SavingTransaction::where('user_id', $userId)->value('savings_id')
            ?? Saving::first()?->id;

        $balances = $this->calculateBalances($userId);
        $availMandatory = $balances['availMandatory'];
        $availSecondary = $balances['availSecondary'];

        if ($availMandatory > 0 && $availSecondary > 0) {
            $this->withdrawalType = 'full';
        } elseif ($availMandatory > 0) {
            $this->withdrawalType = 'mandatory';
        } elseif ($availSecondary > 0) {
            $this->withdrawalType = 'secondary';
        } else {
            $this->withdrawalType = 'full';
        }

        // Always start from 0 as requested
        $this->mandatoryAmount = 0;
        $this->secondaryAmount = 0;

        $this->reason = '';
        $this->isWithdrawalModalOpen = true;
    }

    public function closeWithdrawalModal()
    {
        $this->isWithdrawalModalOpen = false;
        $this->resetErrorBag();
    }

    public function updatedWithdrawalType($value)
    {
        if ($value === 'mandatory') {
            $this->secondaryAmount = 0;
        } elseif ($value === 'secondary') {
            $this->mandatoryAmount = 0;
        }
    }

    public function setMaxMandatory()
    {
        $balances = $this->calculateBalances(Auth::id());
        $this->mandatoryAmount = $balances['availMandatory'];
    }

    public function setMaxSecondary()
    {
        $balances = $this->calculateBalances(Auth::id());
        $this->secondaryAmount = $balances['availSecondary'];
    }

    public function setFullWithdrawal()
    {
        $balances = $this->calculateBalances(Auth::id());
        $this->mandatoryAmount = $balances['availMandatory'];
        $this->secondaryAmount = $balances['availSecondary'];
    }

    public function submitWithdrawal()
    {
        $userId = Auth::id();
        $balances = $this->calculateBalances($userId);
        $availMandatory = $balances['availMandatory'];
        $availSecondary = $balances['availSecondary'];

        // Clean numeric values if formatted as string
        if (is_string($this->mandatoryAmount)) {
            $this->mandatoryAmount = (float) preg_replace('/[^0-9]/', '', $this->mandatoryAmount);
        }
        if (is_string($this->secondaryAmount)) {
            $this->secondaryAmount = (float) preg_replace('/[^0-9]/', '', $this->secondaryAmount);
        }

        $this->validate([
            'savingsId' => 'required|exists:savings,id',
            'withdrawalType' => 'required|in:full,mandatory,secondary',
            'mandatoryAmount' => 'numeric|min:0',
            'secondaryAmount' => 'numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $mandAmount = (float) $this->mandatoryAmount;
        $secAmount = (float) $this->secondaryAmount;

        if ($this->withdrawalType === 'mandatory') {
            $secAmount = 0;
            if ($mandAmount <= 0) {
                $this->addError('mandatoryAmount', 'Nominal penarikan syirkah wajib harus lebih dari 0.');
                return;
            }
        } elseif ($this->withdrawalType === 'secondary') {
            $mandAmount = 0;
            if ($secAmount <= 0) {
                $this->addError('secondaryAmount', 'Nominal penarikan syirkah sukarela (SSR) harus lebih dari 0.');
                return;
            }
        } elseif ($this->withdrawalType === 'full') {
            if (($mandAmount + $secAmount) <= 0) {
                $this->addError('mandatoryAmount', 'Total nominal penarikan syirkah harus lebih dari 0.');
                return;
            }
        }

        if ($mandAmount > $availMandatory) {
            $this->addError('mandatoryAmount', 'Saldo Syirkah Wajib tidak mencukupi (Sisa tersedia: Rp ' . number_format($availMandatory, 0, ',', '.') . ').');
            return;
        }

        if ($secAmount > $availSecondary) {
            $this->addError('secondaryAmount', 'Saldo Syirkah Sukarela/SSR tidak mencukupi (Sisa tersedia: Rp ' . number_format($availSecondary, 0, ',', '.') . ').');
            return;
        }

        try {
            SavingTransactionService::createWithdrawalRequest(
                $userId,
                $this->savingsId,
                $this->withdrawalType,
                $mandAmount,
                $secAmount,
                $this->reason ?: null
            );

            $this->closeWithdrawalModal();
            $this->dispatch('notify', 'Pengajuan penarikan syirkah berhasil dikirim. Menunggu verifikasi Manajer Divisi.');
        } catch (\Exception $e) {
            $this->addError('withdrawalType', 'Gagal mengajukan penarikan: ' . $e->getMessage());
        }
    }

    public function cancelWithdrawal($id)
    {
        $withdrawal = SavingWithdrawal::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->find($id);

        if ($withdrawal) {
            SavingTransactionService::deleteWithdrawalRequest($id);
            $this->dispatch('notify', 'Pengajuan penarikan syirkah berhasil dibatalkan.');
        }
    }

    public function openWithdrawalDetailModal($id)
    {
        $this->selectedWithdrawal = SavingWithdrawal::with(['masterSaving', 'approver', 'payer', 'user'])
            ->where('user_id', Auth::id())
            ->find($id);

        if ($this->selectedWithdrawal) {
            $this->isWithdrawalDetailModalOpen = true;
        }
    }

    public function closeWithdrawalDetailModal()
    {
        $this->isWithdrawalDetailModalOpen = false;
        $this->selectedWithdrawal = null;
    }

    private function calculateBalances(string $userId): array
    {
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

        // Pending/Accepted/Approved active withdrawals reservation for new submission quota
        $pendingMandatory = (float) SavingWithdrawal::where('user_id', $userId)
            ->whereIn('status', ['pending', 'accepted', 'approved'])
            ->sum(DB::raw('COALESCE(approved_mandatory_amount, mandatory_amount)'));

        $pendingSecondary = (float) SavingWithdrawal::where('user_id', $userId)
            ->whereIn('status', ['pending', 'accepted', 'approved'])
            ->sum(DB::raw('COALESCE(approved_secondary_amount, secondary_amount)'));

        $availMandatory = max(0.0, $saldoWajib - $pendingMandatory);
        $availSecondary = max(0.0, $saldoSukarela - $pendingSecondary);
        $availTotal = $availMandatory + $availSecondary;

        return [
            'saldoWajib' => $saldoWajib,
            'saldoSukarela' => $saldoSukarela,
            'totalSaldo' => $totalSaldo,
            'totalCreditAll' => $totalCreditAll,
            'totalDebitAll' => $totalDebitAll,
            'pendingMandatory' => $pendingMandatory,
            'pendingSecondary' => $pendingSecondary,
            'availMandatory' => $availMandatory,
            'availSecondary' => $availSecondary,
            'availTotal' => $availTotal,
        ];
    }

    public function render()
    {
        $userId = Auth::id();
        $balances = $this->calculateBalances($userId);

        // 1. Query transactions for ledger table (strictly approved only)
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
            ->paginate($this->perPage, ['*'], 'transactionsPage');

        // 2. Query user's withdrawal requests
        $withdrawals = SavingWithdrawal::with(['masterSaving', 'approver', 'payer', 'ownerApprover', 'savingTransaction'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'withdrawalsPage');

        $savingsList = Saving::orderBy('savings_name')->get();

        return view('livewire.user.syirkah-history-component', [
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
            'savingsList' => $savingsList,
            'saldoWajib' => $balances['saldoWajib'],
            'saldoSukarela' => $balances['saldoSukarela'],
            'totalSaldo' => $balances['totalSaldo'],
            'totalCreditAll' => $balances['totalCreditAll'],
            'totalDebitAll' => $balances['totalDebitAll'],
            'availMandatory' => $balances['availMandatory'],
            'availSecondary' => $balances['availSecondary'],
            'availTotal' => $balances['availTotal'],
            'totalTransactionsCount' => SavingTransaction::where('user_id', $userId)->where('status', 'approved')->count(),
            'pendingWithdrawalsCount' => SavingWithdrawal::where('user_id', $userId)->where('status', 'pending')->count(),
        ])->layout('layouts.app');
    }
}
