<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\Saving;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SavingTransactionComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $month = '';
    public $type = '';
    public $division = '';
    public $statusFilter = ''; // '', 'pending', 'approved', 'rejected'

    // Bulk Actions State
    public $selectedTransactions = [];
    public $selectAll = false;

    // Modal Pencairan
    public $withdrawalModalOpen = false;
    public $withdrawal_user_id = '';
    public $withdrawal_savings_id = '';
    public $withdrawal_amount = 0;
    public $withdrawal_type = 'secondary'; // mandatory or secondary
    public $withdrawal_description = '';

    // Modal Edit Nominal (Khusus Syirkah Group)
    public $editNominalModalOpen = false;
    public $editingTransactionId = null;
    public $edit_mandatory_amount = 0;
    public $edit_secondary_amount = 0;
    public $editingTransaction = null;

    // Modal Reject
    public $rejectModalOpen = false;
    public $rejectTransactionId = null;
    public $rejection_reason = '';
    public $isBulkReject = false;

    // Modal Delete Permanent
    public $isDeleteModalOpen = false;
    public $deleteTransactionId = null;
    public $isBulkDelete = false;

    protected $updatesQueryString = ['statusFilter', 'month', 'type', 'division'];

    public function updatedSelectAll($value)
    {
        if ($value) {
            $query = $this->buildQuery();
            $this->selectedTransactions = $query->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedTransactions = [];
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingMonth()
    {
        $this->resetPage();
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingType()
    {
        $this->resetPage();
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingDivision()
    {
        $this->resetPage();
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function approve($transactionId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menyetujui mutasi.');
        }

        \App\Services\SavingTransactionService::approveTransaction($transactionId, Auth::id());
        $this->dispatch('notify', 'Mutasi syirkah berhasil disetujui & saldo berhasil diperbarui.');
    }

    public function openRejectModal($transactionId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menolak mutasi.');
        }

        $this->rejectTransactionId = $transactionId;
        $this->isBulkReject = false;
        $this->rejection_reason = '';
        $this->rejectModalOpen = true;
    }

    public function openBulkRejectModal()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menolak mutasi.');
        }

        if (empty($this->selectedTransactions)) {
            $this->dispatch('notify', 'Pilih minimal satu transaksi untuk ditolak.');
            return;
        }

        $this->isBulkReject = true;
        $this->rejectTransactionId = null;
        $this->rejection_reason = '';
        $this->rejectModalOpen = true;
    }

    public function closeRejectModal()
    {
        $this->rejectModalOpen = false;
        $this->rejectTransactionId = null;
        $this->rejection_reason = '';
        $this->isBulkReject = false;
    }

    public function submitReject()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menolak mutasi.');
        }

        if ($this->isBulkReject) {
            $count = \App\Services\SavingTransactionService::bulkReject(
                $this->selectedTransactions,
                Auth::id(),
                $this->rejection_reason ?: 'Ditolak oleh admin Syirkah'
            );
            $this->selectedTransactions = [];
            $this->selectAll = false;
            $this->dispatch('notify', "Sebanyak {$count} transaksi syirkah berhasil ditolak.");
        } else {
            \App\Services\SavingTransactionService::rejectTransaction(
                $this->rejectTransactionId,
                Auth::id(),
                $this->rejection_reason ?: 'Ditolak oleh admin Syirkah'
            );
            $this->dispatch('notify', 'Mutasi syirkah berhasil ditolak.');
        }

        $this->closeRejectModal();
    }

    public function bulkApprove()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menyetujui mutasi.');
        }

        if (empty($this->selectedTransactions)) {
            $this->dispatch('notify', 'Pilih minimal satu transaksi untuk disetujui.');
            return;
        }

        $count = \App\Services\SavingTransactionService::bulkApprove($this->selectedTransactions, Auth::id());
        $this->selectedTransactions = [];
        $this->selectAll = false;
        $this->dispatch('notify', "Sebanyak {$count} transaksi syirkah berhasil disetujui & saldo berhasil diperbarui.");
    }

    public function openDeleteModal($transactionId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menghapus data mutasi.');
        }

        $this->deleteTransactionId = $transactionId;
        $this->isBulkDelete = false;
        $this->isDeleteModalOpen = true;
    }

    public function openBulkDeleteModal()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menghapus data mutasi.');
        }

        if (empty($this->selectedTransactions)) {
            $this->dispatch('notify', 'Pilih minimal satu transaksi untuk dihapus.');
            return;
        }

        $this->isBulkDelete = true;
        $this->deleteTransactionId = null;
        $this->isDeleteModalOpen = true;
    }

    public function closeDeleteModal()
    {
        $this->isDeleteModalOpen = false;
        $this->deleteTransactionId = null;
        $this->isBulkDelete = false;
    }

    public function confirmDelete()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Superadmin yang berhak menghapus data mutasi.');
        }

        if ($this->isBulkDelete) {
            $count = \App\Services\SavingTransactionService::bulkDelete($this->selectedTransactions);
            $this->selectedTransactions = [];
            $this->selectAll = false;
            $this->dispatch('notify', "Sebanyak {$count} data mutasi syirkah berhasil dihapus permanen & saldo berjalan dihitung ulang.");
        } else {
            \App\Services\SavingTransactionService::deleteTransaction($this->deleteTransactionId);
            $this->dispatch('notify', 'Data mutasi syirkah berhasil dihapus permanen & saldo berjalan dihitung ulang.');
        }

        $this->closeDeleteModal();
    }

    public function openEditNominalModal($transactionId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya group syirkah yang berhak mengedit nominal mutasi.');
        }

        $tx = SavingTransaction::with(['user', 'masterSaving'])->findOrFail($transactionId);
        $this->editingTransactionId = $tx->id;
        $this->editingTransaction = $tx;
        $this->edit_mandatory_amount = (float) $tx->mandatory_amount;
        $this->edit_secondary_amount = (float) $tx->secondary_amount;
        $this->editNominalModalOpen = true;
    }

    public function closeEditNominalModal()
    {
        $this->editNominalModalOpen = false;
        $this->editingTransactionId = null;
        $this->editingTransaction = null;
        $this->reset(['edit_mandatory_amount', 'edit_secondary_amount']);
    }

    public function updateNominal()
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya group syirkah yang berhak mengedit nominal mutasi.');
        }

        $this->validate([
            'edit_mandatory_amount' => 'required|numeric|min:0',
            'edit_secondary_amount' => 'required|numeric|min:0',
        ], [
            'edit_mandatory_amount.required' => 'Nominal Mutasi Wajib wajib diisi.',
            'edit_mandatory_amount.numeric' => 'Nominal Mutasi Wajib harus berupa angka.',
            'edit_mandatory_amount.min' => 'Nominal Mutasi Wajib tidak boleh negatif.',
            'edit_secondary_amount.required' => 'Nominal Mutasi Sukarela wajib diisi.',
            'edit_secondary_amount.numeric' => 'Nominal Mutasi Sukarela harus berupa angka.',
            'edit_secondary_amount.min' => 'Nominal Mutasi Sukarela tidak boleh negatif.',
        ]);

        $tx = SavingTransaction::findOrFail($this->editingTransactionId);

        DB::transaction(function () use ($tx) {
            $tx->update([
                'mandatory_amount' => $this->edit_mandatory_amount,
                'secondary_amount' => $this->edit_secondary_amount,
                'updated_at' => now(),
            ]);

            // Recalculate running balance and summaries
            \App\Services\SavingTransactionService::recalculateUserTransactions($tx->user_id, $tx->savings_id);
        });

        $this->closeEditNominalModal();
        $this->dispatch('notify', 'Nominal mutasi syirkah berhasil diperbarui.');
    }

    public function openWithdrawalModal()
    {
        $this->reset(['withdrawal_user_id', 'withdrawal_savings_id', 'withdrawal_amount', 'withdrawal_description', 'withdrawal_type']);
        $this->withdrawalModalOpen = true;
    }

    public function closeWithdrawalModal()
    {
        $this->withdrawalModalOpen = false;
    }

    public function processWithdrawal()
    {
        $this->validate([
            'withdrawal_user_id' => 'required|exists:users,id',
            'withdrawal_savings_id' => 'required|exists:savings,id',
            'withdrawal_amount' => 'required|numeric|min:1',
            'withdrawal_type' => 'required|in:mandatory,secondary,both',
        ]);

        DB::beginTransaction();
        try {
            // Calculate true balance dynamically from approved transactions
            $summary = \App\Models\SavingSummary::firstOrCreate(
                ['user_id' => $this->withdrawal_user_id, 'savings_id' => $this->withdrawal_savings_id],
                ['total_mandatory' => 0, 'total_secondary' => 0]
            );
            $balanceMandatory = $summary->total_mandatory;
            $balanceSecondary = $summary->total_secondary;

            $withdrawMandatory = 0;
            $withdrawSecondary = 0;

            if ($this->withdrawal_type === 'mandatory') {
                if ($this->withdrawal_amount > $balanceMandatory) {
                    $this->addError('withdrawal_amount', 'Saldo Wajib tidak mencukupi (Tersedia: Rp ' . number_format($balanceMandatory, 0, ',', '.') . ').');
                    DB::rollBack();
                    return;
                }
                $withdrawMandatory = $this->withdrawal_amount;
            } elseif ($this->withdrawal_type === 'secondary') {
                if ($this->withdrawal_amount > $balanceSecondary) {
                    $this->addError('withdrawal_amount', 'Saldo Sukarela tidak mencukupi (Tersedia: Rp ' . number_format($balanceSecondary, 0, ',', '.') . ').');
                    DB::rollBack();
                    return;
                }
                $withdrawSecondary = $this->withdrawal_amount;
            } elseif ($this->withdrawal_type === 'both') {
                if ($this->withdrawal_amount > ($balanceMandatory + $balanceSecondary)) {
                    $this->addError('withdrawal_amount', 'Total Saldo (Wajib + Sukarela) tidak mencukupi.');
                    DB::rollBack();
                    return;
                }
                
                // Prioritaskan potong dari Sukarela dulu
                if ($this->withdrawal_amount <= $balanceSecondary) {
                    $withdrawSecondary = $this->withdrawal_amount;
                } else {
                    $withdrawSecondary = $balanceSecondary;
                    $withdrawMandatory = $this->withdrawal_amount - $balanceSecondary;
                }
            }

            $newBalanceMandatory = max(0, $balanceMandatory - $withdrawMandatory);
            $newBalanceSecondary = max(0, $balanceSecondary - $withdrawSecondary);

            $isDirectApproved = Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin;

            SavingTransaction::create([
                'user_id' => $this->withdrawal_user_id,
                'savings_id' => $this->withdrawal_savings_id,
                'transaction_type' => 'withdrawal',
                'mandatory_amount' => $withdrawMandatory,
                'secondary_amount' => $withdrawSecondary,
                'balance_mandatory' => $isDirectApproved ? $newBalanceMandatory : 0,
                'balance_secondary' => $isDirectApproved ? $newBalanceSecondary : 0,
                'status' => $isDirectApproved ? 'approved' : 'pending',
                'approved_by' => $isDirectApproved ? Auth::id() : null,
                'approval_date' => $isDirectApproved ? now() : null,
                'description' => $this->withdrawal_description ?: 'Pencairan Syirkah',
            ]);

            if ($isDirectApproved) {
                \App\Services\SavingTransactionService::recalculateUserTransactions($this->withdrawal_user_id, $this->withdrawal_savings_id);
            }

            DB::commit();
            $this->closeWithdrawalModal();
            $this->dispatch('notify', 'Pencairan berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('withdrawal_user_id', 'Gagal memproses pencairan: ' . $e->getMessage());
        }
    }

    private function buildQuery()
    {
        $query = SavingTransaction::with(['user.division', 'masterSaving', 'approver']);

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('user', function($subQ) {
                    $subQ->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('nip', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('masterSaving', function($subQ) {
                    $subQ->where('savings_name', 'like', '%' . $this->search . '%');
                });
            });
        }

        if ($this->month) {
            try {
                $date = Carbon::parse($this->month);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {}
        }

        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
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
        $transactions = $query->latest()->paginate(15);
        $users = User::where('group', 'user')->whereIn('status', ['active', 'suspend'])->orderBy('name')->get();
        $savingsList = Saving::orderBy('savings_name')->get();

        // Calculate dynamic true balance for summary from approved transactions
        $summaryQuery = \App\Models\SavingSummary::query();
        if ($this->search) {
            $summaryQuery->where(function($q) {
                $q->whereHas('user', function($subQ) {
                    $subQ->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('nip', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('masterSaving', function($subQ) {
                    $subQ->where('savings_name', 'like', '%' . $this->search . '%');
                });
            });
        }
        
        if ($this->division) {
            $summaryQuery->whereHas('user', function($q) {
                $q->where('division_id', $this->division);
            });
        }
        
        $totalWajib = (float) $summaryQuery->sum('total_mandatory');
        $totalSukarela = (float) $summaryQuery->sum('total_secondary');

        // Pending Counter
        $pendingCount = SavingTransaction::where('status', 'pending')->count();
        $pendingNominal = (float) SavingTransaction::where('status', 'pending')->sum(DB::raw('mandatory_amount + secondary_amount'));

        return view('livewire.payroll.saving-transaction-component', [
            'transactions' => $transactions,
            'users' => $users,
            'savingsList' => $savingsList,
            'totalWajib' => $totalWajib,
            'totalSukarela' => $totalSukarela,
            'pendingCount' => $pendingCount,
            'pendingNominal' => $pendingNominal,
        ])->layout('layouts.app');
    }
}

