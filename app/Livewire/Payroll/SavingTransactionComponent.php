<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use App\Models\User;
use App\Models\Saving;
use App\Models\Division;
use App\Services\SavingTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SavingTransactionComponent extends Component
{
    use WithPagination;

    // Active View Tab ('withdrawals' or 'transactions') - Default to withdrawals
    public $activeTab = 'withdrawals';

    // Filters for Mutasi Transaksi
    public $search = '';
    public $month = '';
    public $type = '';
    public $division = '';
    public $statusFilter = ''; // '', 'pending', 'approved', 'rejected'

    // Filters for Pengajuan Penarikan
    public $withdrawalSearch = '';
    public $withdrawalMonth = '';
    public $withdrawalStatusFilter = ''; // '', 'pending', 'accepted', 'paid', 'rejected'
    public $withdrawalDivision = '';

    // Bulk Actions State (Mutasi)
    public $selectedTransactions = [];
    public $selectAll = false;

    // Modal Pencairan Langsung (Admin Mutasi)
    public $withdrawalModalOpen = false;
    public $withdrawal_user_id = '';
    public $withdrawal_savings_id = '';
    public $withdrawal_amount = 0;
    public $withdrawal_type = 'secondary'; // mandatory or secondary
    public $withdrawal_description = '';

    // Modal Edit Nominal (Khusus Syirkah Group / Owner)
    public $editNominalModalOpen = false;
    public $editingTransactionId = null;
    public $edit_mandatory_amount = 0;
    public $edit_secondary_amount = 0;
    public $editingTransaction = null;

    // Modal Reject Mutasi
    public $rejectModalOpen = false;
    public $rejectTransactionId = null;
    public $rejection_reason = '';
    public $isBulkReject = false;

    // Modal Delete Permanent Mutasi
    public $isDeleteModalOpen = false;
    public $deleteTransactionId = null;
    public $isBulkDelete = false;

    // Modal Reject Pengajuan Penarikan
    public $rejectWithdrawalModalOpen = false;
    public $rejectWithdrawalId = null;
    public $withdrawalRejectionReason = '';

    // Modal Owner Approval Pengajuan Penarikan
    public $ownerApproveModalOpen = false;
    public $ownerApproveWithdrawalId = null;
    public $ownerApprovedAmount = 0;
    public $ownerApproveNote = '';
    public $selectedOwnerWithdrawal = null;

    // Modal Detail Pengajuan Penarikan
    public $detailWithdrawalModalOpen = false;
    public $selectedWithdrawal = null;

    protected $queryString = [
        'activeTab' => ['except' => 'withdrawals'],
        'statusFilter' => ['except' => ''],
        'month' => ['except' => ''],
        'type' => ['except' => ''],
        'division' => ['except' => ''],
        'withdrawalStatusFilter' => ['except' => ''],
    ];

    public function mount()
    {
        if (Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Role Superadmin tidak memiliki akses ke fitur Syirkah.');
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage('transactionsPage');
        $this->resetPage('withdrawalsPage');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $query = $this->buildTransactionsQuery();
            $this->selectedTransactions = $query->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedTransactions = [];
        }
    }

    public function updatingSearch()
    {
        $this->resetPage('transactionsPage');
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingMonth()
    {
        $this->resetPage('transactionsPage');
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingType()
    {
        $this->resetPage('transactionsPage');
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingDivision()
    {
        $this->resetPage('transactionsPage');
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingStatusFilter()
    {
        $this->resetPage('transactionsPage');
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }

    public function updatingWithdrawalSearch()
    {
        $this->resetPage('withdrawalsPage');
    }

    public function updatingWithdrawalMonth()
    {
        $this->resetPage('withdrawalsPage');
    }

    public function updatingWithdrawalStatusFilter()
    {
        $this->resetPage('withdrawalsPage');
    }

    public function updatingWithdrawalDivision()
    {
        $this->resetPage('withdrawalsPage');
    }

    /* =========================================================================
     * MUTASI TRANSACTIONS ACTIONS
     * ========================================================================= */

    public function approve($transactionId)
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin) {
            abort(403, 'Akses Ditolak: Role Superadmin tidak memiliki akses ke fitur Syirkah.');
        }

        $tx = SavingTransaction::with('user')->findOrFail($transactionId);

        if ($user->group === 'admin' && !$user->isSyirkah && !$user->isOwner && !$user->isPayroll) {
            if ($tx->user?->division_id !== $user->division_id) {
                abort(403, 'Akses Ditolak: Anda hanya berwenang menyetujui mutasi karyawan di divisi Anda.');
            }
        } elseif (!$user->isSyirkah && !$user->isOwner && !$user->isPayroll) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui mutasi.');
        }

        SavingTransactionService::approveTransaction($transactionId, Auth::id());
        $this->dispatch('notify', 'Mutasi syirkah berhasil disetujui & saldo berhasil diperbarui.');
    }

    public function openRejectModal($transactionId)
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin) {
            abort(403, 'Akses Ditolak: Role Superadmin tidak memiliki akses ke fitur Syirkah.');
        }

        $tx = SavingTransaction::with('user')->findOrFail($transactionId);

        if ($user->group === 'admin' && !$user->isSyirkah && !$user->isOwner && !$user->isPayroll) {
            if ($tx->user?->division_id !== $user->division_id) {
                abort(403, 'Akses Ditolak: Anda hanya berwenang menolak mutasi karyawan di divisi Anda.');
            }
        } elseif (!$user->isSyirkah && !$user->isOwner && !$user->isPayroll) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak mutasi.');
        }

        $this->rejectTransactionId = $transactionId;
        $this->isBulkReject = false;
        $this->rejection_reason = '';
        $this->rejectModalOpen = true;
    }

    public function openBulkRejectModal()
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isSyirkah && !$user->isOwner)) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Owner yang berhak menolak mutasi massal.');
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
        $user = Auth::user();
        if (!$user || $user->isSuperadmin) {
            abort(403, 'Akses Ditolak: Role Superadmin tidak memiliki akses ke fitur Syirkah.');
        }

        if ($this->isBulkReject) {
            if (!$user->isSyirkah && !$user->isOwner) {
                abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Owner yang berhak menolak mutasi massal.');
            }
            $count = SavingTransactionService::bulkReject(
                $this->selectedTransactions,
                Auth::id(),
                $this->rejection_reason ?: 'Ditolak oleh admin'
            );
            $this->selectedTransactions = [];
            $this->selectAll = false;
            $this->dispatch('notify', "Sebanyak {$count} transaksi syirkah berhasil ditolak.");
        } else {
            $tx = SavingTransaction::with('user')->findOrFail($this->rejectTransactionId);
            if ($user->group === 'admin' && !$user->isSyirkah && !$user->isOwner && !$user->isPayroll) {
                if ($tx->user?->division_id !== $user->division_id) {
                    abort(403, 'Akses Ditolak: Anda hanya berwenang menolak mutasi karyawan di divisi Anda.');
                }
            } elseif (!$user->isSyirkah && !$user->isOwner && !$user->isPayroll) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak mutasi.');
            }

            SavingTransactionService::rejectTransaction(
                $this->rejectTransactionId,
                Auth::id(),
                $this->rejection_reason ?: 'Ditolak oleh admin'
            );
            $this->dispatch('notify', 'Mutasi syirkah berhasil ditolak.');
        }

        $this->closeRejectModal();
    }

    public function bulkApprove()
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isSyirkah && !$user->isOwner)) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Owner yang berhak menyetujui mutasi massal.');
        }

        if (empty($this->selectedTransactions)) {
            $this->dispatch('notify', 'Pilih minimal satu transaksi untuk disetujui.');
            return;
        }

        $count = SavingTransactionService::bulkApprove($this->selectedTransactions, Auth::id());
        $this->selectedTransactions = [];
        $this->selectAll = false;
        $this->dispatch('notify', "Sebanyak {$count} transaksi syirkah berhasil disetujui & saldo berhasil diperbarui.");
    }

    public function openDeleteModal($transactionId)
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isSyirkah && !$user->isOwner)) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Owner yang berhak menghapus data mutasi.');
        }

        $this->deleteTransactionId = $transactionId;
        $this->isBulkDelete = false;
        $this->isDeleteModalOpen = true;
    }

    public function openBulkDeleteModal()
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isSyirkah && !$user->isOwner)) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Owner yang berhak menghapus data mutasi.');
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
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isSyirkah && !$user->isOwner)) {
            abort(403, 'Akses Ditolak: Hanya user dengan role Syirkah / Owner yang berhak menghapus data mutasi.');
        }

        if ($this->isBulkDelete) {
            $count = SavingTransactionService::bulkDelete($this->selectedTransactions);
            $this->selectedTransactions = [];
            $this->selectAll = false;
            $this->dispatch('notify', "Sebanyak {$count} data mutasi syirkah berhasil dihapus permanen & saldo berjalan dihitung ulang.");
        } else {
            SavingTransactionService::deleteTransaction($this->deleteTransactionId);
            $this->dispatch('notify', 'Data mutasi syirkah berhasil dihapus permanen & saldo berjalan dihitung ulang.');
        }

        $this->closeDeleteModal();
    }

    public function openEditNominalModal($transactionId)
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isSyirkah && !$user->isOwner)) {
            abort(403, 'Akses Ditolak: Hanya role Syirkah / Owner yang berhak mengedit nominal mutasi.');
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
        $user = Auth::user();
        if (!$user || $user->isSuperadmin || (!$user->isSyirkah && !$user->isOwner)) {
            abort(403, 'Akses Ditolak: Hanya role Syirkah / Owner yang berhak mengedit nominal mutasi.');
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

            SavingTransactionService::recalculateUserTransactions($tx->user_id, $tx->savings_id);
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

        $user = User::onlyWorkingEmployee()->findOrFail($this->withdrawal_user_id);

        DB::beginTransaction();
        try {
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
                
                if ($this->withdrawal_amount <= $balanceSecondary) {
                    $withdrawSecondary = $this->withdrawal_amount;
                } else {
                    $withdrawSecondary = $balanceSecondary;
                    $withdrawMandatory = $this->withdrawal_amount - $balanceSecondary;
                }
            }

            $newBalanceMandatory = max(0, $balanceMandatory - $withdrawMandatory);
            $newBalanceSecondary = max(0, $balanceSecondary - $withdrawSecondary);

            $isDirectApproved = Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin || Auth::user()?->isOwner;

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
                SavingTransactionService::recalculateUserTransactions($this->withdrawal_user_id, $this->withdrawal_savings_id);
            }

            DB::commit();
            $this->closeWithdrawalModal();
            $this->dispatch('notify', 'Pencairan berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('withdrawal_user_id', 'Gagal memproses pencairan: ' . $e->getMessage());
        }
    }

    /* =========================================================================
     * WITHDRAWAL REQUESTS LIFECYCLE ACTIONS (PENDING -> ACCEPTED -> PAID / REJECTED)
     * ========================================================================= */

    private function authorizeWithdrawalAction(SavingWithdrawal $withdrawal): void
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            abort(403, 'Akses Ditolak: Anda harus login.');
        }

        if ($currentUser->isSuperadmin) {
            abort(403, 'Akses Ditolak: Role Superadmin tidak memiliki akses ke fitur Syirkah.');
        }

        // Syirkah, Owner, Payroll have global access
        if ($currentUser->isSyirkah || $currentUser->isOwner || $currentUser->isPayroll) {
            return;
        }

        // Division Admin can only manage employees in their division
        if ($currentUser->group === 'admin') {
            if ($withdrawal->user?->division_id !== $currentUser->division_id) {
                abort(403, 'Akses Ditolak: Anda hanya berwenang memproses pengajuan karyawan di divisi Anda.');
            }
            return;
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses pengajuan ini.');
    }

    public function approveWithdrawal($withdrawalId)
    {
        $withdrawal = SavingWithdrawal::with('user')->findOrFail($withdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        try {
            SavingTransactionService::approveWithdrawalRequest($withdrawalId, Auth::id());
            $this->dispatch('notify', 'Pengajuan penarikan syirkah berhasil disetujui (ACCEPTED) dan diteruskan ke Owner.');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal menyetujui pengajuan: ' . $e->getMessage());
        }
    }

    public function openOwnerApproveModal($withdrawalId)
    {
        $withdrawal = SavingWithdrawal::with(['user.division', 'user.paymentMethod', 'masterSaving'])->findOrFail($withdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        $this->selectedOwnerWithdrawal = $withdrawal;
        $this->ownerApproveWithdrawalId = $withdrawalId;
        $this->ownerApprovedAmount = $withdrawal->approved_total_amount !== null ? $withdrawal->approved_total_amount : $withdrawal->total_amount;
        $this->ownerApproveNote = $withdrawal->owner_note ?: '';
        $this->ownerApproveModalOpen = true;
    }

    public function closeOwnerApproveModal()
    {
        $this->ownerApproveModalOpen = false;
        $this->ownerApproveWithdrawalId = null;
        $this->ownerApprovedAmount = 0;
        $this->ownerApproveNote = '';
        $this->selectedOwnerWithdrawal = null;
    }

    public function submitOwnerApprove()
    {
        if (!$this->ownerApproveWithdrawalId) return;

        $withdrawal = SavingWithdrawal::with('user')->findOrFail($this->ownerApproveWithdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        $nominal = (float) $this->ownerApprovedAmount;
        if ($nominal <= 0) {
            $this->dispatch('notify', 'Nominal yang disetujui harus lebih dari 0.');
            return;
        }

        if ($nominal > $withdrawal->total_amount) {
            $nominal = (float) $withdrawal->total_amount;
        }

        try {
            SavingTransactionService::approveByOwnerWithdrawalRequest(
                $this->ownerApproveWithdrawalId,
                Auth::id(),
                $nominal,
                $this->ownerApproveNote ?: 'Disetujui oleh Owner'
            );
            $this->closeOwnerApproveModal();
            $this->dispatch('notify', 'Pengajuan berhasil disetujui Owner (APPROVED) dan masuk ke antrean pembayaran.');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal menyimpan persetujuan Owner: ' . $e->getMessage());
        }
    }

    public function openRejectWithdrawalModal($withdrawalId)
    {
        $withdrawal = SavingWithdrawal::with('user')->findOrFail($withdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        $this->rejectWithdrawalId = $withdrawalId;
        $this->withdrawalRejectionReason = '';
        $this->rejectWithdrawalModalOpen = true;
    }

    public function closeRejectWithdrawalModal()
    {
        $this->rejectWithdrawalModalOpen = false;
        $this->rejectWithdrawalId = null;
        $this->withdrawalRejectionReason = '';
    }

    public function submitRejectWithdrawal()
    {
        if (!$this->rejectWithdrawalId) return;

        $withdrawal = SavingWithdrawal::with('user')->findOrFail($this->rejectWithdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        try {
            SavingTransactionService::rejectWithdrawalRequest(
                $this->rejectWithdrawalId,
                Auth::id(),
                $this->withdrawalRejectionReason ?: 'Ditolak oleh Admin/Atasan'
            );
            $this->closeRejectWithdrawalModal();
            $this->dispatch('notify', 'Pengajuan penarikan syirkah berhasil ditolak (REJECTED).');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal menolak pengajuan: ' . $e->getMessage());
        }
    }

    public function markAsPaidWithdrawal($withdrawalId)
    {
        $withdrawal = SavingWithdrawal::with('user')->findOrFail($withdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        try {
            SavingTransactionService::markAsPaidWithdrawalRequest($withdrawalId, Auth::id());
            $this->dispatch('notify', 'Pengajuan penarikan berhasil ditandai telah dibayarkan (PAID) dan saldo mutasi telah dipotong.');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    public function openDetailWithdrawalModal($withdrawalId)
    {
        $this->selectedWithdrawal = SavingWithdrawal::with([
            'user.division',
            'masterSaving',
            'approver',
            'payer',
            'savingTransaction'
        ])->find($withdrawalId);

        if ($this->selectedWithdrawal) {
            $this->detailWithdrawalModalOpen = true;
        }
    }

    public function closeDetailWithdrawalModal()
    {
        $this->detailWithdrawalModalOpen = false;
        $this->selectedWithdrawal = null;
    }

    public function deleteWithdrawal($withdrawalId)
    {
        if (!Auth::user()?->isSyirkah && !Auth::user()?->isOwner) {
            abort(403, 'Akses Ditolak: Hanya Syirkah / Owner yang berhak menghapus data pengajuan.');
        }

        SavingTransactionService::deleteWithdrawalRequest($withdrawalId);
        $this->dispatch('notify', 'Data pengajuan penarikan berhasil dihapus.');
    }

    /* =========================================================================
     * QUERIES & DATA RENDERING
     * ========================================================================= */

    private function applyDivisionScope($query, ?string $userRelation = 'user')
    {
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->group === 'admin' && !$currentUser->isSuperadmin && !$currentUser->isSyirkah && !$currentUser->isOwner && !$currentUser->isPayroll) {
            if ($userRelation === null) {
                if ($currentUser->division_id) {
                    $query->where('division_id', $currentUser->division_id);
                }
            } else {
                $query->whereHas($userRelation, function($q) use ($currentUser) {
                    if ($currentUser->division_id) {
                        $q->where('division_id', $currentUser->division_id);
                    }
                });
            }
        }
        return $query;
    }

    private function buildTransactionsQuery()
    {
        $query = SavingTransaction::with(['user.division', 'masterSaving', 'approver'])
            ->whereHas('user', function($q) {
                $q->onlyEmployee();
            });

        $query = $this->applyDivisionScope($query, 'user');

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

    private function buildWithdrawalsQuery()
    {
        $query = SavingWithdrawal::with(['user.division', 'masterSaving', 'approver', 'ownerApprover', 'payer', 'savingTransaction'])
            ->whereHas('user', function($q) {
                $q->onlyEmployee();
            });

        $query = $this->applyDivisionScope($query, 'user');

        if ($this->withdrawalSearch) {
            $query->where(function($q) {
                $q->whereHas('user', function($subQ) {
                    $subQ->where('name', 'like', '%' . $this->withdrawalSearch . '%')
                         ->orWhere('nip', 'like', '%' . $this->withdrawalSearch . '%');
                })
                ->orWhere('reason', 'like', '%' . $this->withdrawalSearch . '%');
            });
        }

        if ($this->withdrawalMonth) {
            try {
                $date = Carbon::parse($this->withdrawalMonth);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {}
        }

        if ($this->withdrawalStatusFilter) {
            $query->where('status', $this->withdrawalStatusFilter);
        }

        if ($this->withdrawalDivision) {
            $query->whereHas('user', function($q) {
                $q->where('division_id', $this->withdrawalDivision);
            });
        }

        return $query;
    }

    public function render()
    {
        $currentUser = Auth::user();
        $isDivisionScoped = ($currentUser && $currentUser->group === 'admin' && !$currentUser->isSuperadmin && !$currentUser->isSyirkah && !$currentUser->isOwner && !$currentUser->isPayroll);
        $adminDivisionName = $isDivisionScoped ? ($currentUser->division?->name ?? 'Divisi Anda') : null;

        // 1. Transactions List
        $transactionsQuery = $this->buildTransactionsQuery();
        $transactions = $transactionsQuery->latest()->paginate(15, ['*'], 'transactionsPage');

        // 2. Withdrawals List
        $withdrawalsQuery = $this->buildWithdrawalsQuery();
        $withdrawals = $withdrawalsQuery->latest()->paginate(15, ['*'], 'withdrawalsPage');

        $usersQuery = User::onlyWorkingEmployee()->orderBy('name');
        $usersQuery = $this->applyDivisionScope($usersQuery, null);
        $users = $usersQuery->get();

        $savingsList = Saving::orderBy('savings_name')->get();

        $divisionsListQuery = Division::orderBy('name');
        if ($isDivisionScoped && $currentUser->division_id) {
            $divisionsListQuery->where('id', $currentUser->division_id);
        }
        $divisionsList = $divisionsListQuery->get();

        // 3. True balances calculated dynamically from approved transactions (Scoped per division for admin)
        $approvedDepositQuery = SavingTransaction::whereHas('user', fn($q) => $q->onlyEmployee())->where('status', 'approved')->where('transaction_type', 'deposit');
        $approvedWithdrawalQuery = SavingTransaction::whereHas('user', fn($q) => $q->onlyEmployee())->where('status', 'approved')->where('transaction_type', 'withdrawal');

        $approvedDepositQuery = $this->applyDivisionScope($approvedDepositQuery, 'user');
        $approvedWithdrawalQuery = $this->applyDivisionScope($approvedWithdrawalQuery, 'user');

        if ($this->search) {
            $searchFilter = function($q) {
                $q->whereHas('user', function($subQ) {
                    $subQ->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('nip', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('masterSaving', function($subQ) {
                    $subQ->where('savings_name', 'like', '%' . $this->search . '%');
                });
            };
            $approvedDepositQuery->where($searchFilter);
            $approvedWithdrawalQuery->where($searchFilter);
        }

        if ($this->division) {
            $divisionFilter = function($q) {
                $q->whereHas('user', function($subQ) {
                    $subQ->where('division_id', $this->division);
                });
            };
            $approvedDepositQuery->where($divisionFilter);
            $approvedWithdrawalQuery->where($divisionFilter);
        }

        $totalWajib = max(0.0, (float) $approvedDepositQuery->sum('mandatory_amount') - (float) $approvedWithdrawalQuery->sum('mandatory_amount'));
        $totalSukarela = max(0.0, (float) $approvedDepositQuery->sum('secondary_amount') - (float) $approvedWithdrawalQuery->sum('secondary_amount'));

        // Pending Mutasi Counter (Scoped)
        $pendingQuery = SavingTransaction::whereHas('user', fn($q) => $q->onlyEmployee())->where('status', 'pending');
        $pendingQuery = $this->applyDivisionScope($pendingQuery, 'user');
        $pendingCount = $pendingQuery->count();
        $pendingNominal = (float) $pendingQuery->sum(DB::raw('mandatory_amount + secondary_amount'));

        // Withdrawal Counters (Scoped)
        $scopedWdBase = SavingWithdrawal::whereHas('user', fn($q) => $q->onlyEmployee());
        $scopedWdBase = $this->applyDivisionScope($scopedWdBase, 'user');

        $pendingWithdrawalsCount = (clone $scopedWdBase)->where('status', 'pending')->count();
        $pendingWithdrawalsNominal = (float) (clone $scopedWdBase)->where('status', 'pending')->sum('total_amount');
        $acceptedWithdrawalsCount = (clone $scopedWdBase)->where('status', 'accepted')->count();
        $paidWithdrawalsCount = (clone $scopedWdBase)->where('status', 'paid')->count();
        $rejectedWithdrawalsCount = (clone $scopedWdBase)->where('status', 'rejected')->count();

        return view('livewire.payroll.saving-transaction-component', [
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
            'users' => $users,
            'savingsList' => $savingsList,
            'divisionsList' => $divisionsList,
            'totalWajib' => $totalWajib,
            'totalSukarela' => $totalSukarela,
            'pendingCount' => $pendingCount,
            'pendingNominal' => $pendingNominal,
            'pendingWithdrawalsCount' => $pendingWithdrawalsCount,
            'pendingWithdrawalsNominal' => $pendingWithdrawalsNominal,
            'acceptedWithdrawalsCount' => $acceptedWithdrawalsCount,
            'paidWithdrawalsCount' => $paidWithdrawalsCount,
            'rejectedWithdrawalsCount' => $rejectedWithdrawalsCount,
            'isDivisionScoped' => $isDivisionScoped,
            'adminDivisionName' => $adminDivisionName,
        ])->layout('layouts.app');
    }
}
