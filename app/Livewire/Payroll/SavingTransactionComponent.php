<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
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
    use WithPagination, WithFileUploads;

    // Active View Tab ('transactions' or 'withdrawals') - Default to transactions
    public $activeTab = 'transactions';

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
    public $withdrawal_transfer_proof = null;

    // Modal Pembayaran Penarikan Syirkah (Upload Bukti Transfer)
    public $payWithdrawalModalOpen = false;
    public $payingWithdrawalId = null;
    public $payingWithdrawal = null;
    public $paymentProof = null;
    public $paymentNote = '';

    // Viewer Bukti Transfer (Lightbox Modal)
    public $selectedProofUrl = null;
    public $isProofModalOpen = false;

    // Modal Upload / Edit Bukti Transfer (Belakangan / Re-upload)
    public $isUploadProofModalOpen = false;
    public $proofTargetType = 'withdrawal'; // 'withdrawal' or 'transaction'
    public $proofTargetId = null;
    public $proofTargetModel = null;
    public $newTransferProof = null;

    // Modal Setoran Langsung (Admin Mutasi / Anggota Non-Absen)
    public $depositModalOpen = false;
    public $deposit_user_id = '';
    public $deposit_savings_id = '';
    public $deposit_mandatory_amount = 0;
    public $deposit_secondary_amount = 0;
    public $deposit_description = '';
    public $deposit_date = '';
    public $deposit_transfer_proof = null;

    // Modal Edit Nominal (Khusus Syirkah Group / Owner)
    public $editNominalModalOpen = false;
    public $editingTransactionId = null;
    public $edit_mandatory_amount = 0;
    public $edit_secondary_amount = 0;
    public $edit_description = '';
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
        'activeTab' => ['except' => 'transactions'],
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
            if (!$user->hasDivisionAccess($tx->user?->division_id)) {
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
            if (!$user->hasDivisionAccess($tx->user?->division_id)) {
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
                if (!$user->hasDivisionAccess($tx->user?->division_id)) {
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
        $this->edit_description = $tx->description ?: '';
        $this->editNominalModalOpen = true;
    }

    public function closeEditNominalModal()
    {
        $this->editNominalModalOpen = false;
        $this->editingTransactionId = null;
        $this->editingTransaction = null;
        $this->reset(['edit_mandatory_amount', 'edit_secondary_amount', 'edit_description']);
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
            'edit_description' => 'nullable|string|max:255',
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
                'description' => $this->edit_description ?: $tx->description,
                'updated_at' => now(),
            ]);

            SavingTransactionService::recalculateUserTransactions($tx->user_id, $tx->savings_id);
        });

        $this->closeEditNominalModal();
        $this->dispatch('notify', 'Data mutasi syirkah berhasil diperbarui.');
    }

    #[On('open-deposit-modal')]
    public function openDepositModal()
    {
        $this->reset(['deposit_user_id', 'deposit_savings_id', 'deposit_mandatory_amount', 'deposit_secondary_amount', 'deposit_description', 'deposit_date', 'deposit_transfer_proof']);
        $this->deposit_date = date('Y-m-d');
        $this->depositModalOpen = true;
    }

    public function closeDepositModal()
    {
        $this->depositModalOpen = false;
        $this->deposit_transfer_proof = null;
    }

    public function processDeposit()
    {
        $user = Auth::user();
        if (!$user || $user->isSuperadmin) {
            abort(403, 'Akses Ditolak: Role Superadmin tidak memiliki akses ke fitur Syirkah.');
        }

        $this->validate([
            'deposit_user_id' => 'required|exists:users,id',
            'deposit_savings_id' => 'required|exists:savings,id',
            'deposit_mandatory_amount' => 'required|numeric|min:0',
            'deposit_secondary_amount' => 'required|numeric|min:0',
            'deposit_date' => 'nullable|date',
            'deposit_transfer_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'deposit_user_id.required' => 'Pilih karyawan / anggota syirkah.',
            'deposit_savings_id.required' => 'Pilih program syirkah.',
            'deposit_transfer_proof.mimes' => 'Format bukti transfer harus JPG, PNG, WEBP, atau PDF.',
            'deposit_transfer_proof.max' => 'Ukuran bukti transfer maksimal 5MB.',
        ]);

        $mandAmount = (float) $this->deposit_mandatory_amount;
        $secAmount = (float) $this->deposit_secondary_amount;

        if ($mandAmount <= 0 && $secAmount <= 0) {
            $this->addError('deposit_mandatory_amount', 'Salah satu nominal (Wajib atau Sukarela) harus lebih dari 0.');
            return;
        }

        $targetUser = User::onlyWorkingEmployee()->findOrFail($this->deposit_user_id);

        if ($user->group === 'admin' && !$user->isSyirkah && !$user->isOwner && !$user->isPayroll) {
            if (!$user->hasDivisionAccess($targetUser->division_id)) {
                abort(403, 'Akses Ditolak: Anda hanya berwenang mencatat setoran anggota di divisi Anda.');
            }
        }

        DB::beginTransaction();
        try {
            $isDirectApproved = $user->isSyirkah || $user->isOwner || $user->isPayroll;

            $proofPath = null;
            if ($this->deposit_transfer_proof) {
                $proofPath = $this->deposit_transfer_proof->store('syirkah/proofs', 'public');
            }

            $txDate = !empty($this->deposit_date) ? Carbon::parse($this->deposit_date . ' ' . date('H:i:s')) : now();

            $tx = SavingTransaction::create([
                'user_id' => $this->deposit_user_id,
                'savings_id' => $this->deposit_savings_id,
                'transaction_type' => 'deposit',
                'mandatory_amount' => $mandAmount,
                'secondary_amount' => $secAmount,
                'balance_mandatory' => 0,
                'balance_secondary' => 0,
                'status' => $isDirectApproved ? 'approved' : 'pending',
                'approved_by' => $isDirectApproved ? Auth::id() : null,
                'approval_date' => $isDirectApproved ? now() : null,
                'description' => $this->deposit_description ?: 'Setoran Syirkah Manual',
                'transfer_proof' => $proofPath,
                'created_at' => $txDate,
                'updated_at' => now(),
            ]);

            if ($isDirectApproved) {
                SavingTransactionService::recalculateUserTransactions($this->deposit_user_id, $this->deposit_savings_id);
            }

            DB::commit();
            $this->closeDepositModal();
            $this->dispatch('notify', 'Setoran syirkah manual berhasil dicatat & saldo diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('deposit_user_id', 'Gagal memproses setoran: ' . $e->getMessage());
        }
    }

    #[On('open-withdrawal-modal')]
    public function openWithdrawalModal()
    {
        $this->reset(['withdrawal_user_id', 'withdrawal_savings_id', 'withdrawal_amount', 'withdrawal_description', 'withdrawal_type', 'withdrawal_transfer_proof']);
        $this->withdrawalModalOpen = true;
    }

    public function closeWithdrawalModal()
    {
        $this->withdrawalModalOpen = false;
        $this->withdrawal_transfer_proof = null;
    }

    public function processWithdrawal()
    {
        $this->validate([
            'withdrawal_user_id' => 'required|exists:users,id',
            'withdrawal_savings_id' => 'required|exists:savings,id',
            'withdrawal_amount' => 'required|numeric|min:1',
            'withdrawal_type' => 'required|in:mandatory,secondary,both',
            'withdrawal_transfer_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'withdrawal_transfer_proof.mimes' => 'Format bukti transfer harus JPG, PNG, WEBP, atau PDF.',
            'withdrawal_transfer_proof.max' => 'Ukuran bukti transfer maksimal 5MB.',
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

            $directProofPath = null;
            if ($this->withdrawal_transfer_proof) {
                $directProofPath = $this->withdrawal_transfer_proof->store('syirkah/proofs', 'public');
            }

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
                'transfer_proof' => $directProofPath,
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
            if (!$currentUser->hasDivisionAccess($withdrawal->user?->division_id)) {
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

    public function openPayWithdrawalModal($withdrawalId)
    {
        $this->resetErrorBag();
        $withdrawal = SavingWithdrawal::with(['user.division', 'user.paymentMethod', 'masterSaving'])->findOrFail($withdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        $this->payingWithdrawalId = $withdrawalId;
        $this->payingWithdrawal = $withdrawal;
        $this->paymentProof = null;
        $this->paymentNote = '';
        $this->payWithdrawalModalOpen = true;
    }

    public function closePayWithdrawalModal()
    {
        $this->payWithdrawalModalOpen = false;
        $this->payingWithdrawalId = null;
        $this->payingWithdrawal = null;
        $this->paymentProof = null;
        $this->paymentNote = '';
        $this->resetErrorBag();
    }

    public function submitPayWithdrawal()
    {
        if (!$this->payingWithdrawalId) return;

        $withdrawal = SavingWithdrawal::with('user')->findOrFail($this->payingWithdrawalId);
        $this->authorizeWithdrawalAction($withdrawal);

        $this->validate([
            'paymentProof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'paymentProof.mimes' => 'Format file bukti transfer harus berupa JPG, PNG, WEBP, atau PDF.',
            'paymentProof.max' => 'Ukuran file bukti transfer tidak boleh melebihi 5MB.',
        ]);

        try {
            $proofPath = null;
            if ($this->paymentProof) {
                $proofPath = $this->paymentProof->store('syirkah/proofs', 'public');
            }

            SavingTransactionService::markAsPaidWithdrawalRequest(
                $this->payingWithdrawalId,
                Auth::id(),
                $proofPath
            );

            $this->closePayWithdrawalModal();
            $this->dispatch('notify', 'Pengajuan penarikan berhasil dibayarkan (PAID) dan bukti transfer berhasil disimpan.');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    public function markAsPaidWithdrawal($withdrawalId)
    {
        $this->openPayWithdrawalModal($withdrawalId);
    }

    public function viewProof($url)
    {
        $this->selectedProofUrl = $url;
        $this->isProofModalOpen = true;
    }

    public function closeProofModal()
    {
        $this->isProofModalOpen = false;
        $this->selectedProofUrl = null;
    }

    public function openUploadProofModal($id, $type = 'withdrawal')
    {
        $this->proofTargetType = $type;
        $this->proofTargetId = $id;

        if ($type === 'withdrawal') {
            $this->proofTargetModel = SavingWithdrawal::with(['user.division', 'masterSaving'])->findOrFail($id);
        } else {
            $this->proofTargetModel = SavingTransaction::with(['user.division', 'masterSaving', 'savingWithdrawal'])->findOrFail($id);
        }

        $this->newTransferProof = null;
        $this->isUploadProofModalOpen = true;
    }

    public function closeUploadProofModal()
    {
        $this->isUploadProofModalOpen = false;
        $this->proofTargetId = null;
        $this->proofTargetModel = null;
        $this->newTransferProof = null;
        $this->resetValidation('newTransferProof');
    }

    public function saveTransferProof()
    {
        if (!$this->proofTargetId) return;

        $this->validate([
            'newTransferProof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'newTransferProof.required' => 'Pilih file bukti transfer terlebih dahulu.',
            'newTransferProof.mimes' => 'Format file bukti transfer harus berupa JPG, PNG, WEBP, atau PDF.',
            'newTransferProof.max' => 'Ukuran file bukti transfer tidak boleh melebihi 5MB.',
        ]);

        try {
            SavingTransactionService::updateTransferProof(
                $this->proofTargetType,
                $this->proofTargetId,
                $this->newTransferProof
            );

            // If detail withdrawal modal is currently open, refresh it
            if ($this->detailWithdrawalModalOpen && $this->selectedWithdrawal && $this->selectedWithdrawal->id == $this->proofTargetId) {
                $this->selectedWithdrawal = $this->selectedWithdrawal->fresh();
            }

            $this->closeUploadProofModal();
            $this->dispatch('notify', 'Bukti transfer berhasil disimpan dan file lama telah dibersihkan.');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal memperbarui bukti transfer: ' . $e->getMessage());
        }
    }

    public function deleteTransferProof()
    {
        if (!$this->proofTargetId) return;

        try {
            SavingTransactionService::deleteTransferProof(
                $this->proofTargetType,
                $this->proofTargetId
            );

            // If detail withdrawal modal is currently open, refresh it
            if ($this->detailWithdrawalModalOpen && $this->selectedWithdrawal && $this->selectedWithdrawal->id == $this->proofTargetId) {
                $this->selectedWithdrawal = $this->selectedWithdrawal->fresh();
            }

            $this->closeUploadProofModal();
            $this->dispatch('notify', 'Bukti transfer berhasil dihapus dari sistem.');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal menghapus bukti transfer: ' . $e->getMessage());
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
            $accessibleIds = $currentUser->getAccessibleDivisionIds();
            if ($userRelation === null) {
                $query->whereIn('division_id', $accessibleIds);
            } else {
                $query->whereHas($userRelation, function($q) use ($accessibleIds) {
                    $q->whereIn('division_id', $accessibleIds);
                });
            }
        }
        return $query;
    }

    private function buildTransactionsQuery()
    {
        $query = SavingTransaction::with(['user.division', 'masterSaving', 'approver', 'savingWithdrawal'])
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
        $adminDivisionName = $isDivisionScoped ? ($currentUser->hasMultipleDivisions() ? $currentUser->getAccessibleDivisions()->pluck('name')->implode(', ') : ($currentUser->division?->name ?? 'Divisi Anda')) : null;

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
        if ($isDivisionScoped) {
            $divisionsListQuery->whereIn('id', $currentUser->getAccessibleDivisionIds());
        }
        $divisionsList = $divisionsListQuery->get();

        // 3. Transactions Metrics (Fully scoped and respecting all filters: month, type, statusFilter, division, search)
        $txStatsQuery = $this->buildTransactionsQuery();
        $filteredTransactionsCount = (clone $txStatsQuery)->count();

        // Calculate Wajib, Sukarela, and Total based on current filters
        if ($this->type === 'deposit') {
            $totalWajib = (float) (clone $txStatsQuery)->sum('mandatory_amount');
            $totalSukarela = (float) (clone $txStatsQuery)->sum('secondary_amount');
            $totalMutasiAmount = $totalWajib + $totalSukarela;
        } elseif ($this->type === 'withdrawal') {
            $totalWajib = (float) (clone $txStatsQuery)->sum('mandatory_amount');
            $totalSukarela = (float) (clone $txStatsQuery)->sum('secondary_amount');
            $totalMutasiAmount = $totalWajib + $totalSukarela;
        } else {
            // All types (deposit & withdrawal)
            if ($this->statusFilter && $this->statusFilter !== 'approved') {
                // When explicitly viewing pending or rejected across all types, sum the exact transaction nominals
                $totalWajib = (float) (clone $txStatsQuery)->sum('mandatory_amount');
                $totalSukarela = (float) (clone $txStatsQuery)->sum('secondary_amount');
                $totalMutasiAmount = $totalWajib + $totalSukarela;
            } else {
                // If viewing approved (or all status without explicit non-approved filter):
                // Net Saldo / Mutasi: Total Deposit - Total Withdrawal
                if (!$this->statusFilter) {
                    $depWajib = (float) (clone $txStatsQuery)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('mandatory_amount');
                    $withdWajib = (float) (clone $txStatsQuery)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('mandatory_amount');
                    $depSukarela = (float) (clone $txStatsQuery)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('secondary_amount');
                    $withdSukarela = (float) (clone $txStatsQuery)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('secondary_amount');
                } else {
                    $depWajib = (float) (clone $txStatsQuery)->where('transaction_type', 'deposit')->sum('mandatory_amount');
                    $withdWajib = (float) (clone $txStatsQuery)->where('transaction_type', 'withdrawal')->sum('mandatory_amount');
                    $depSukarela = (float) (clone $txStatsQuery)->where('transaction_type', 'deposit')->sum('secondary_amount');
                    $withdSukarela = (float) (clone $txStatsQuery)->where('transaction_type', 'withdrawal')->sum('secondary_amount');
                }

                $totalWajib = max(0.0, $depWajib - $withdWajib);
                $totalSukarela = max(0.0, $depSukarela - $withdSukarela);
                $totalMutasiAmount = $totalWajib + $totalSukarela;
            }
        }

        // Status Breakdown for Transactions (Scoped to month, type, division, search)
        $txFilterWithoutStatus = SavingTransaction::whereHas('user', fn($q) => $q->onlyEmployee());
        $txFilterWithoutStatus = $this->applyDivisionScope($txFilterWithoutStatus, 'user');

        if ($this->search) {
            $search = $this->search;
            $txFilterWithoutStatus->where(function($q) use ($search) {
                $q->whereHas('user', function($subQ) use ($search) {
                    $subQ->where('name', 'like', '%' . $search . '%')
                         ->orWhere('nip', 'like', '%' . $search . '%');
                })
                ->orWhereHas('masterSaving', function($subQ) use ($search) {
                    $subQ->where('savings_name', 'like', '%' . $search . '%');
                });
            });
        }

        if ($this->month) {
            try {
                $date = Carbon::parse($this->month);
                $txFilterWithoutStatus->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {}
        }

        if ($this->type) {
            $txFilterWithoutStatus->where('transaction_type', $this->type);
        }

        if ($this->division) {
            $txFilterWithoutStatus->whereHas('user', function($q) {
                $q->where('division_id', $this->division);
            });
        }

        $pendingCount = (clone $txFilterWithoutStatus)->where('status', 'pending')->count();
        $pendingNominal = (float) (clone $txFilterWithoutStatus)->where('status', 'pending')->sum(DB::raw('mandatory_amount + secondary_amount'));
        $approvedCount = (clone $txFilterWithoutStatus)->where('status', 'approved')->count();
        $approvedNominal = (float) (clone $txFilterWithoutStatus)->where('status', 'approved')->sum(DB::raw('mandatory_amount + secondary_amount'));
        $rejectedCount = (clone $txFilterWithoutStatus)->where('status', 'rejected')->count();
        $rejectedNominal = (float) (clone $txFilterWithoutStatus)->where('status', 'rejected')->sum(DB::raw('mandatory_amount + secondary_amount'));
        $withdrawalTxCount = (clone $txFilterWithoutStatus)->where('transaction_type', 'withdrawal')->count();

        // Debit (Keluar) & Credit (Masuk) metrics for currently active filter
        $creditQuery = (clone $txStatsQuery)->where('transaction_type', 'deposit');
        $debitQuery = (clone $txStatsQuery)->where('transaction_type', 'withdrawal');

        if (!$this->statusFilter) {
            $totalCredit = (float) (clone $creditQuery)->where('status', 'approved')->sum(DB::raw('mandatory_amount + secondary_amount'));
            $creditCount = (clone $creditQuery)->where('status', 'approved')->count();

            $totalDebit = (float) (clone $debitQuery)->where('status', 'approved')->sum(DB::raw('mandatory_amount + secondary_amount'));
            $debitCount = (clone $debitQuery)->where('status', 'approved')->count();
        } else {
            $totalCredit = (float) (clone $creditQuery)->sum(DB::raw('mandatory_amount + secondary_amount'));
            $creditCount = (clone $creditQuery)->count();

            $totalDebit = (float) (clone $debitQuery)->sum(DB::raw('mandatory_amount + secondary_amount'));
            $debitCount = (clone $debitQuery)->count();
        }

        // 4. Withdrawals Metrics (Scoped to withdrawalMonth, withdrawalDivision, withdrawalSearch)
        $wdFilterWithoutStatus = SavingWithdrawal::whereHas('user', fn($q) => $q->onlyEmployee());
        $wdFilterWithoutStatus = $this->applyDivisionScope($wdFilterWithoutStatus, 'user');

        if ($this->withdrawalSearch) {
            $wdSearch = $this->withdrawalSearch;
            $wdFilterWithoutStatus->where(function($q) use ($wdSearch) {
                $q->whereHas('user', function($subQ) use ($wdSearch) {
                    $subQ->where('name', 'like', '%' . $wdSearch . '%')
                         ->orWhere('nip', 'like', '%' . $wdSearch . '%');
                })
                ->orWhere('reason', 'like', '%' . $wdSearch . '%');
            });
        }

        if ($this->withdrawalMonth) {
            try {
                $date = Carbon::parse($this->withdrawalMonth);
                $wdFilterWithoutStatus->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {}
        }

        if ($this->withdrawalDivision) {
            $wdFilterWithoutStatus->whereHas('user', function($q) {
                $q->where('division_id', $this->withdrawalDivision);
            });
        }

        $pendingWithdrawalsCount = (clone $wdFilterWithoutStatus)->where('status', 'pending')->count();
        $pendingWithdrawalsNominal = (float) (clone $wdFilterWithoutStatus)->where('status', 'pending')->sum('total_amount');
        $acceptedWithdrawalsCount = (clone $wdFilterWithoutStatus)->where('status', 'accepted')->count();
        $acceptedWithdrawalsNominal = (float) (clone $wdFilterWithoutStatus)->where('status', 'accepted')->sum('total_amount');
        $paidWithdrawalsCount = (clone $wdFilterWithoutStatus)->where('status', 'paid')->count();
        $paidWithdrawalsNominal = (float) (clone $wdFilterWithoutStatus)->where('status', 'paid')->sum('total_amount');
        $rejectedWithdrawalsCount = (clone $wdFilterWithoutStatus)->where('status', 'rejected')->count();
        $rejectedWithdrawalsNominal = (float) (clone $wdFilterWithoutStatus)->where('status', 'rejected')->sum('total_amount');

        $totalWithdrawalsCount = (clone $wdFilterWithoutStatus)->count();
        $totalWithdrawalsNominal = (float) (clone $wdFilterWithoutStatus)->sum('total_amount');

        // Selected division name for context label
        $selectedDivisionModel = $this->division ? Division::find($this->division) : null;
        $selectedDivisionName = $selectedDivisionModel?->name;
        $selectedWithdrawalDivisionModel = $this->withdrawalDivision ? Division::find($this->withdrawalDivision) : null;
        $selectedWithdrawalDivisionName = $selectedWithdrawalDivisionModel?->name;

        return view('livewire.payroll.saving-transaction-component', [
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
            'users' => $users,
            'savingsList' => $savingsList,
            'divisionsList' => $divisionsList,
            'totalWajib' => $totalWajib,
            'totalSukarela' => $totalSukarela,
            'totalMutasiAmount' => $totalMutasiAmount,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'creditCount' => $creditCount,
            'debitCount' => $debitCount,
            'withdrawalTxCount' => $withdrawalTxCount,
            'filteredTransactionsCount' => $filteredTransactionsCount,
            'pendingCount' => $pendingCount,
            'pendingNominal' => $pendingNominal,
            'approvedCount' => $approvedCount,
            'approvedNominal' => $approvedNominal,
            'rejectedCount' => $rejectedCount,
            'rejectedNominal' => $rejectedNominal,
            'pendingWithdrawalsCount' => $pendingWithdrawalsCount,
            'pendingWithdrawalsNominal' => $pendingWithdrawalsNominal,
            'acceptedWithdrawalsCount' => $acceptedWithdrawalsCount,
            'acceptedWithdrawalsNominal' => $acceptedWithdrawalsNominal,
            'paidWithdrawalsCount' => $paidWithdrawalsCount,
            'paidWithdrawalsNominal' => $paidWithdrawalsNominal,
            'rejectedWithdrawalsCount' => $rejectedWithdrawalsCount,
            'rejectedWithdrawalsNominal' => $rejectedWithdrawalsNominal,
            'totalWithdrawalsCount' => $totalWithdrawalsCount,
            'totalWithdrawalsNominal' => $totalWithdrawalsNominal,
            'selectedDivisionName' => $selectedDivisionName,
            'selectedWithdrawalDivisionName' => $selectedWithdrawalDivisionName,
            'isDivisionScoped' => $isDivisionScoped,
            'adminDivisionName' => $adminDivisionName,
        ])->layout('layouts.app');
    }
}
