<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Loan;
use App\Models\User;
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
    public $description = '';

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
        if ($this->loan_amount > 0 && $this->tenor_months > 0) {
            $this->installment_amount = ceil($this->loan_amount / $this->tenor_months);
        } else {
            $this->installment_amount = 0;
        }
    }

    public function openCreateModal()
    {
        $this->reset(['user_id', 'loan_amount', 'tenor_months', 'installment_amount', 'description']);
        $this->createModalOpen = true;
    }

    public function closeCreateModal()
    {
        $this->createModalOpen = false;
    }

    public function storeLoan()
    {
        $this->validate([
            'user_id' => 'required|exists:users,id',
            'loan_amount' => 'required|numeric|min:1',
            'tenor_months' => 'required|integer|min:1',
        ]);

        $this->calculateInstallment();

        Loan::create([
            'user_id' => $this->user_id,
            'loan_amount' => $this->loan_amount,
            'tenor_months' => $this->tenor_months,
            'installment_amount' => $this->installment_amount,
            'remaining_balance' => $this->loan_amount,
            'status' => 'pending', // Baru diajukan: butuh approval
            'approved_by' => null,
            'approval_date' => null,
            'description' => $this->description,
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', 'Pengajuan pinjaman berhasil dibuat dan menunggu persetujuan.');
    }

    public function approveLoan($loanId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui pinjaman.');
        }

        $loan = Loan::findOrFail($loanId);
        $loan->update([
            'status' => 'active',
            'approved_by' => Auth::id(),
            'approval_date' => now(),
            'rejection_reason' => null,
        ]);

        $this->dispatch('notify', 'Pinjaman berhasil disetujui dan berstatus aktif.');
    }

    public function openRejectModal($loanId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak pinjaman.');
        }

        $this->rejectLoanId = $loanId;
        $this->isBulkReject = false;
        $this->rejection_reason = '';
        $this->rejectModalOpen = true;
    }

    public function openBulkRejectModal()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
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
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
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
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui pinjaman.');
        }

        if (empty($this->selectedLoans)) {
            $this->dispatch('notify', 'Pilih minimal satu pinjaman untuk disetujui.');
            return;
        }

        Loan::whereIn('id', $this->selectedLoans)->where('status', 'pending')->update([
            'status' => 'active',
            'approved_by' => Auth::id(),
            'approval_date' => now(),
            'rejection_reason' => null,
        ]);

        $count = count($this->selectedLoans);
        $this->selectedLoans = [];
        $this->selectAll = false;
        $this->dispatch('notify', "Sebanyak {$count} pinjaman berhasil disetujui & diaktifkan.");
    }

    public function openDeleteModal($loanId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data pinjaman.');
        }

        $this->deleteLoanId = $loanId;
        $this->isBulkDelete = false;
        $this->isDeleteModalOpen = true;
    }

    public function openBulkDeleteModal()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data pinjaman.');
        }

        if (empty($this->selectedLoans)) {
            $this->dispatch('notify', 'Pilih minimal satu pinjaman untuk dihapus.');
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
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isPayroll && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data pinjaman.');
        }

        if ($this->isBulkDelete) {
            \Illuminate\Support\Facades\DB::transaction(function () {
                \App\Models\LoanInstallment::whereIn('loan_id', $this->selectedLoans)->delete();
                Loan::whereIn('id', $this->selectedLoans)->delete();
            });
            $count = count($this->selectedLoans);
            $this->selectedLoans = [];
            $this->selectAll = false;
            $this->dispatch('notify', "Sebanyak {$count} data pinjaman berhasil dihapus permanen.");
        } else {
            \Illuminate\Support\Facades\DB::transaction(function () {
                \App\Models\LoanInstallment::where('loan_id', $this->deleteLoanId)->delete();
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
        $query = Loan::with(['user.division', 'approver', 'installments']);

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
        $users = User::where('group', 'user')->whereIn('status', ['active', 'suspend'])->orderBy('name')->get();

        // Statistics
        $pendingCount = Loan::where('status', 'pending')->count();
        $pendingNominal = (float) Loan::where('status', 'pending')->sum('loan_amount');

        $activeCount = Loan::whereIn('status', ['approved', 'active'])->count();
        $activeBalance = (float) Loan::whereIn('status', ['approved', 'active'])->sum('remaining_balance');

        $paidOffCount = Loan::where('status', 'paid_off')->count();

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

