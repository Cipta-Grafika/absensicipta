<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Loan;
use App\Models\User;
use App\Models\Saving;
use App\Models\LoanInstallment;
use App\Models\SavingTransaction;
use App\Services\SavingTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoanComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $division = '';

    // Bulk selection state
    public $selectedLoans = [];
    public $selectAll = false;

    // Modal Create Loan
    public $createModalOpen = false;
    public $user_id = '';
    public $loan_amount = 0;
    public $tenor_months = 1;
    public $payment_source = 'payroll';
    public $description = '';

    // Real-time Syirkah Balance for Selected User
    public $user_syirkah_mandatory = 0;
    public $user_syirkah_secondary = 0;

    // Modal Reject Loan
    public $rejectModalOpen = false;
    public $rejectLoanId = null;
    public $rejection_reason = '';
    public $isBulkReject = false;

    // Modal Delete Permanent Loan
    public $isDeleteModalOpen = false;
    public $deleteLoanId = null;
    public $isBulkDelete = false;

    // Computed Properties for Loan
    public $installment_amount = 0;

    protected $updatesQueryString = ['status', 'division'];

    public function updatedSelectAll($value)
    {
        if ($value) {
            $query = $this->buildQuery();
            $this->selectedLoans = $query->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedLoans = [];
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedLoans = [];
        $this->selectAll = false;
    }

    public function updatingStatus()
    {
        $this->resetPage();
        $this->selectedLoans = [];
        $this->selectAll = false;
    }

    public function updatingDivision()
    {
        $this->resetPage();
        $this->selectedLoans = [];
        $this->selectAll = false;
    }

    public function updatedUserId($value)
    {
        $this->loadUserSyirkahBalance($value);
    }

    public function loadUserSyirkahBalance($userId)
    {
        if ($userId) {
            $depMan = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('mandatory_amount');
            $wdMan = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('mandatory_amount');
            $this->user_syirkah_mandatory = max(0.0, $depMan - $wdMan);

            $depSec = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('secondary_amount');
            $wdSec = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('secondary_amount');
            $this->user_syirkah_secondary = max(0.0, $depSec - $wdSec);
        } else {
            $this->user_syirkah_mandatory = 0;
            $this->user_syirkah_secondary = 0;
        }
    }

    public function updatedLoanAmount()
    {
        $this->calculateInstallment();
    }

    public function updatedTenorMonths()
    {
        $this->calculateInstallment();
    }

    public function calculateInstallment()
    {
        if ($this->payment_source === 'payroll') {
            if ($this->loan_amount > 0 && $this->tenor_months > 0) {
                $this->installment_amount = ceil($this->loan_amount / $this->tenor_months);
            } else {
                $this->installment_amount = 0;
            }
        } else {
            $this->installment_amount = $this->loan_amount;
        }
    }

    public function openCreateModal()
    {
        $this->reset(['user_id', 'loan_amount', 'tenor_months', 'payment_source', 'installment_amount', 'description', 'user_syirkah_mandatory', 'user_syirkah_secondary']);
        $this->createModalOpen = true;
    }

    public function closeCreateModal()
    {
        $this->createModalOpen = false;
    }

    public function storeLoan()
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'loan_amount' => 'required|numeric|min:1',
            'payment_source' => 'required|in:payroll,syirkah_mandatory,syirkah_secondary,syirkah_all',
        ];

        if ($this->payment_source === 'payroll') {
            $rules['tenor_months'] = 'required|integer|min:1';
        }

        $this->validate($rules);

        $user = User::onlyWorkingEmployee()->findOrFail($this->user_id);

        // Validate syirkah balance sufficiency
        if ($this->payment_source === 'syirkah_mandatory' && $this->loan_amount > $this->user_syirkah_mandatory) {
            $this->addError('loan_amount', 'Nominal pinjaman melebihi Saldo Syirkah Wajib karyawan (Rp ' . number_format($this->user_syirkah_mandatory, 0, ',', '.') . ').');
            return;
        }

        if ($this->payment_source === 'syirkah_secondary' && $this->loan_amount > $this->user_syirkah_secondary) {
            $this->addError('loan_amount', 'Nominal pinjaman melebihi Saldo Syirkah SSR karyawan (Rp ' . number_format($this->user_syirkah_secondary, 0, ',', '.') . ').');
            return;
        }

        if ($this->payment_source === 'syirkah_all' && $this->loan_amount > ($this->user_syirkah_mandatory + $this->user_syirkah_secondary)) {
            $this->addError('loan_amount', 'Nominal pinjaman melebihi Total Saldo Syirkah karyawan (Rp ' . number_format($this->user_syirkah_mandatory + $this->user_syirkah_secondary, 0, ',', '.') . ').');
            return;
        }

        $tenor = $this->payment_source === 'payroll' ? $this->tenor_months : 1;
        $installment = $this->payment_source === 'payroll' ? ceil($this->loan_amount / $tenor) : $this->loan_amount;

        Loan::create([
            'user_id' => $this->user_id,
            'loan_amount' => $this->loan_amount,
            'tenor_months' => $tenor,
            'installment_amount' => $installment,
            'remaining_balance' => $this->loan_amount,
            'payment_source' => $this->payment_source,
            'status' => 'pending', // Waiting for approval
            'approved_by' => null,
            'approval_date' => null,
            'description' => $this->description,
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', 'Pengajuan pinjaman berhasil dibuat dan menunggu persetujuan.');
    }

    public function approveLoan($loanId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui pinjaman.');
        }

        $loan = Loan::findOrFail($loanId);

        DB::transaction(function () use ($loan) {
            if ($loan->payment_source === 'payroll') {
                $loan->update([
                    'status' => 'active',
                    'approved_by' => Auth::id(),
                    'approval_date' => now(),
                    'rejection_reason' => null,
                ]);
            } else {
                // Settle immediately via Syirkah withdrawal
                $savingProgram = Saving::first();
                $savingsId = $savingProgram?->id ?? 'default_savings';

                $mandAmount = 0;
                $secAmount = 0;

                if ($loan->payment_source === 'syirkah_mandatory') {
                    $mandAmount = $loan->loan_amount;
                } elseif ($loan->payment_source === 'syirkah_secondary') {
                    $secAmount = $loan->loan_amount;
                } elseif ($loan->payment_source === 'syirkah_all') {
                    // Check available SSR balance
                    $depSec = (float) SavingTransaction::where('user_id', $loan->user_id)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('secondary_amount');
                    $wdSec = (float) SavingTransaction::where('user_id', $loan->user_id)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('secondary_amount');
                    $availSec = max(0.0, $depSec - $wdSec);

                    $secAmount = min($loan->loan_amount, $availSec);
                    $mandAmount = max(0.0, $loan->loan_amount - $secAmount);
                }

                $savingTx = null;
                if ($savingProgram) {
                    $savingTx = SavingTransaction::create([
                        'user_id' => $loan->user_id,
                        'savings_id' => $savingsId,
                        'transaction_type' => 'withdrawal',
                        'mandatory_amount' => $mandAmount,
                        'secondary_amount' => $secAmount,
                        'status' => 'approved',
                        'period_month' => now()->format('Y-m'),
                        'reference_type' => 'loan',
                        'reference_id' => $loan->id,
                        'description' => 'Pelunasan Pinjaman via ' . $loan->payment_source_label,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);

                    SavingTransactionService::recalculateUserTransactions($loan->user_id);
                }

                // Record LoanInstallment
                LoanInstallment::create([
                    'loan_id' => $loan->id,
                    'amount_paid' => $loan->loan_amount,
                    'payment_method' => 'savings_deduction',
                    'saving_transaction_id' => $savingTx?->id,
                    'payroll_id' => null,
                    'status' => 'paid',
                ]);

                // Update loan to paid_off
                $loan->update([
                    'status' => 'paid_off',
                    'remaining_balance' => 0,
                    'approved_by' => Auth::id(),
                    'approval_date' => now(),
                    'rejection_reason' => null,
                ]);
            }
        });

        $this->dispatch('notify', 'Pinjaman berhasil disetujui.');
    }

    public function openRejectModal($loanId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak pinjaman.');
        }

        $this->rejectLoanId = $loanId;
        $this->isBulkReject = false;
        $this->rejection_reason = '';
        $this->rejectModalOpen = true;
    }

    public function openBulkRejectModal()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak pinjaman.');
        }

        if (empty($this->selectedLoans)) {
            $this->dispatch('notify', 'Pilih minimal satu pinjaman untuk ditolak.');
            return;
        }

        $this->isBulkReject = true;
        $this->rejectLoanId = null;
        $this->rejection_reason = '';
        $this->rejectModalOpen = true;
    }

    public function closeRejectModal()
    {
        $this->rejectModalOpen = false;
        $this->rejectLoanId = null;
        $this->rejection_reason = '';
        $this->isBulkReject = false;
    }

    public function submitRejectLoan()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak pinjaman.');
        }

        if ($this->isBulkReject) {
            Loan::whereIn('id', $this->selectedLoans)->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approval_date' => now(),
                'rejection_reason' => $this->rejection_reason ?: 'Ditolak',
            ]);
            $count = count($this->selectedLoans);
            $this->selectedLoans = [];
            $this->selectAll = false;
            $this->dispatch('notify', "Sebanyak {$count} pinjaman berhasil ditolak.");
        } else {
            $loan = Loan::findOrFail($this->rejectLoanId);
            $loan->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approval_date' => now(),
                'rejection_reason' => $this->rejection_reason ?: 'Ditolak',
            ]);
            $this->dispatch('notify', 'Pinjaman berhasil ditolak.');
        }

        $this->closeRejectModal();
    }

    public function bulkApprove()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui pinjaman.');
        }

        if (empty($this->selectedLoans)) {
            $this->dispatch('notify', 'Pilih minimal satu pinjaman untuk disetujui.');
            return;
        }

        $pendingLoans = Loan::whereIn('id', $this->selectedLoans)->where('status', 'pending')->get();
        foreach ($pendingLoans as $loan) {
            $this->approveLoan($loan->id);
        }

        $count = $pendingLoans->count();
        $this->selectedLoans = [];
        $this->selectAll = false;
        $this->dispatch('notify', "Sebanyak {$count} pinjaman berhasil disetujui.");
    }

    public function openDeleteModal($loanId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data pinjaman.');
        }

        $this->deleteLoanId = $loanId;
        $this->isBulkDelete = false;
        $this->isDeleteModalOpen = true;
    }

    public function openBulkDeleteModal()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data pinjaman.');
        }

        if (empty($this->selectedLoans)) {
            $this->dispatch('notify', 'Pilih minimal satu data pinjaman untuk dihapus.');
            return;
        }

        $this->isBulkDelete = true;
        $this->deleteLoanId = null;
        $this->isDeleteModalOpen = true;
    }

    public function closeDeleteModal()
    {
        $this->isDeleteModalOpen = false;
        $this->deleteLoanId = null;
        $this->isBulkDelete = false;
    }

    public function confirmDeleteLoan()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data pinjaman.');
        }

        if ($this->isBulkDelete) {
            DB::transaction(function () {
                LoanInstallment::whereIn('loan_id', $this->selectedLoans)->delete();
                Loan::whereIn('id', $this->selectedLoans)->delete();
            });
            $count = count($this->selectedLoans);
            $this->selectedLoans = [];
            $this->selectAll = false;
            $this->dispatch('notify', "Sebanyak {$count} data pinjaman berhasil dihapus permanen.");
        } else {
            DB::transaction(function () {
                LoanInstallment::where('loan_id', $this->deleteLoanId)->delete();
                Loan::where('id', $this->deleteLoanId)->delete();
            });
            $this->dispatch('notify', 'Data pinjaman berhasil dihapus permanen.');
        }

        $this->closeDeleteModal();
    }

    public function markAsPaidOff($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->update([
            'status' => 'paid_off',
            'remaining_balance' => 0,
        ]);
        $this->dispatch('notify', 'Status pinjaman menjadi lunas.');
    }

    private function buildQuery()
    {
        $query = Loan::with(['user.division', 'approver', 'installments'])
            ->whereHas('user', function($q) {
                $q->onlyEmployee();
            });

        if ($this->search) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status) {
            if ($this->status === 'active') {
                $query->whereIn('status', ['approved', 'active']);
            } else {
                $query->where('status', $this->status);
            }
        }

        if ($this->division) {
            $query->whereHas('user', function($q) {
                $q->where('division_id', $this->division);
            });
        }

        return $query;
    }

    public function render()
    {
        $query = $this->buildQuery();
        $loans = $query->latest()->paginate(15);
        $users = User::onlyWorkingEmployee()
            ->orderBy('name')
            ->get();

        // Statistics filtered for regular employees
        $pendingCount = Loan::whereHas('user', fn($q) => $q->onlyEmployee())->where('status', 'pending')->count();
        $pendingNominal = (float) Loan::whereHas('user', fn($q) => $q->onlyEmployee())->where('status', 'pending')->sum('loan_amount');

        $activeCount = Loan::whereHas('user', fn($q) => $q->onlyEmployee())->whereIn('status', ['approved', 'active'])->count();
        $activeBalance = (float) Loan::whereHas('user', fn($q) => $q->onlyEmployee())->whereIn('status', ['approved', 'active'])->sum('remaining_balance');

        $paidOffCount = Loan::whereHas('user', fn($q) => $q->onlyEmployee())->where('status', 'paid_off')->count();

        return view('livewire.payroll.loan-component', [
            'loans' => $loans,
            'users' => $users,
            'pendingCount' => $pendingCount,
            'pendingNominal' => $pendingNominal,
            'activeCount' => $activeCount,
            'activeBalance' => $activeBalance,
            'paidOffCount' => $paidOffCount,
        ])->layout('layouts.app');
    }
}
