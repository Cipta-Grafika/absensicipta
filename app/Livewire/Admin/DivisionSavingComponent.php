<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\DivisionWallet;
use App\Models\DivisionSavingTransaction;
use App\Services\DivisionSavingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DivisionSavingComponent extends Component
{
    use WithPagination, WithFileUploads;

    // View state & Active Scopes
    public ?int $selectedDivisionId = null;
    public string $selectedMonth = '';
    public string $search = '';
    public string $selectedWalletId = '';
    public string $selectedType = ''; // '', 'credit', 'debit'
    public string $selectedStatus = ''; // '', 'pending', 'approved', 'rejected'

    // Modal Input Transaksi
    public bool $showTransactionModal = false;
    public ?int $form_division_id = null;
    public string $form_wallet_mode = 'select'; // 'select' or 'new'
    public string $form_wallet_id = '';
    public string $form_new_wallet_title = '';
    public string $form_type = 'credit'; // 'credit' or 'debit'
    public ?float $form_amount = null;
    public string $form_date = '';
    public string $form_description = '';
    public string $form_reference_number = '';
    public $form_proof = null;
    public bool $form_auto_approve = true; // Khusus Owner/Superadmin

    // Modal Approval (Khusus Owner/Superadmin)
    public bool $showApproveModal = false;
    public ?string $approvingTxId = null;
    public ?DivisionSavingTransaction $approvingTx = null;

    // Modal Rejection (Khusus Owner/Superadmin)
    public bool $showRejectModal = false;
    public ?string $rejectingTxId = null;
    public ?DivisionSavingTransaction $rejectingTx = null;
    public string $rejectionReason = '';

    // Modal Edit Wallet
    public bool $showEditWalletModal = false;
    public ?string $editingWalletId = null;
    public string $editingWalletTitle = '';
    public string $editingWalletDescription = '';

    // Modal Viewer Bukti
    public bool $showProofModal = false;
    public ?string $selectedProofUrl = null;
    public ?string $selectedProofTitle = null;

    // Error message banner
    public string $errorMessage = '';

    protected function rules(): array
    {
        return [
            'form_division_id' => 'required|exists:divisions,id',
            'form_type' => 'required|in:credit,debit',
            'form_amount' => 'required|numeric|min:1000|max:999999999999',
            'form_date' => 'required|date',
            'form_description' => 'required|string|min:3|max:255',
            'form_reference_number' => 'nullable|string|max:100',
            'form_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // Authorization check: Only Admin, Superadmin, Owner, or Payroll
        if (!$user->isAdmin && !$user->isOwner && !$user->isPayroll && !$user->isSuperadmin) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses Tabungan Divisi.');
        }

        $this->selectedMonth = date('Y-m');
        $this->form_date = date('Y-m-d');

        // Initial division resolution
        if ($user->isOwner || $user->isSuperadmin) {
            $firstDiv = Division::orderBy('name')->first();
            $this->selectedDivisionId = $firstDiv?->id;
            $this->form_division_id = $firstDiv?->id;
            $this->form_auto_approve = true;
        } else {
            $this->selectedDivisionId = $user->division_id;
            $this->form_division_id = $user->division_id;
            $this->form_auto_approve = false;
        }
    }

    public function updatedSelectedDivisionId($val)
    {
        $this->validateDivisionAccess((int)$val);
        $this->selectedWalletId = '';
        $this->resetPage();
    }

    public function updatedSelectedMonth()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedWalletId()
    {
        $this->resetPage();
    }

    public function updatedSelectedType()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    public function openTransactionModal(?string $walletId = null, string $type = 'credit')
    {
        $this->errorMessage = '';
        $this->resetValidation();

        $user = Auth::user();
        $this->form_type = in_array($type, ['credit', 'debit']) ? $type : 'credit';
        $this->form_date = date('Y-m-d');
        $this->form_amount = null;
        $this->form_description = '';
        $this->form_reference_number = '';
        $this->form_proof = null;
        $this->form_auto_approve = $user->isOwner || $user->isSuperadmin;

        if ($user->isOwner || $user->isSuperadmin) {
            $this->form_division_id = $this->selectedDivisionId ?: Division::orderBy('name')->value('id');
        } else {
            $this->form_division_id = $user->division_id;
        }

        if ($walletId) {
            $this->form_wallet_mode = 'select';
            $this->form_wallet_id = $walletId;
            $this->form_new_wallet_title = '';
        } else {
            $existingWallets = DivisionWallet::where('division_id', $this->form_division_id)
                ->where('is_active', true)
                ->get();

            if ($existingWallets->isEmpty()) {
                $this->form_wallet_mode = 'new';
                $this->form_wallet_id = '';
            } else {
                $this->form_wallet_mode = 'select';
                $this->form_wallet_id = $existingWallets->first()->id;
            }
            $this->form_new_wallet_title = '';
        }

        $this->showTransactionModal = true;
    }

    public function closeTransactionModal()
    {
        $this->showTransactionModal = false;
        $this->form_proof = null;
        $this->errorMessage = '';
    }

    public function addAmount(float $nominal)
    {
        $this->form_amount = (float)($this->form_amount ?? 0) + $nominal;
    }

    public function saveTransaction()
    {
        $user = Auth::user();
        $this->errorMessage = '';

        // Security check for division access
        $targetDivId = (int) ($user->isOwner || $user->isSuperadmin ? $this->form_division_id : $user->division_id);
        $this->validateDivisionAccess($targetDivId);

        $rules = $this->rules();
        if ($this->form_wallet_mode === 'new') {
            $rules['form_new_wallet_title'] = 'required|string|min:2|max:100';
        } else {
            $rules['form_wallet_id'] = 'required|exists:division_wallets,id';
        }

        $this->validate($rules);

        // Upload proof if present
        $proofPath = null;
        if ($this->form_proof) {
            $proofPath = $this->form_proof->store('division-saving-proofs', 'public');
        }

        try {
            if ($this->form_wallet_mode === 'new') {
                $walletOrTitle = trim($this->form_new_wallet_title);
            } else {
                $walletOrTitle = DivisionWallet::where('id', $this->form_wallet_id)
                    ->where('division_id', $targetDivId)
                    ->firstOrFail();
            }

            $autoApprove = ($user->isOwner || $user->isSuperadmin) ? $this->form_auto_approve : false;

            $tx = DivisionSavingService::recordTransaction(
                divisionId: $targetDivId,
                walletOrTitle: $walletOrTitle,
                type: $this->form_type,
                amount: (float) $this->form_amount,
                description: $this->form_description,
                actor: $user,
                transactionDate: $this->form_date,
                referenceNumber: $this->form_reference_number ?: null,
                proofAttachmentPath: $proofPath,
                autoApprove: $autoApprove
            );

            $this->closeTransactionModal();

            if ($tx->status === 'approved') {
                $this->dispatch('notify', "Transaksi {$tx->transaction_code} berhasil dicatat dan disetujui langsung!");
            } else {
                $this->dispatch('notify', "Pengajuan transaksi {$tx->transaction_code} berhasil dikirim dan menunggu persetujuan Owner.");
            }
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // --- APPROVAL ACTIONS (OWNER / SUPERADMIN) ---

    public function openApproveModal(string $txId)
    {
        $user = Auth::user();
        if (!$user->isOwner && !$user->isSuperadmin) {
            abort(403);
        }

        $this->errorMessage = '';
        $this->approvingTxId = $txId;
        $this->approvingTx = DivisionSavingTransaction::with(['wallet', 'creator'])->findOrFail($txId);
        $this->showApproveModal = true;
    }

    public function closeApproveModal()
    {
        $this->showApproveModal = false;
        $this->approvingTxId = null;
        $this->approvingTx = null;
        $this->errorMessage = '';
    }

    public function executeApprove()
    {
        $user = Auth::user();
        if (!$user->isOwner && !$user->isSuperadmin) {
            abort(403);
        }

        $this->errorMessage = '';
        try {
            DivisionSavingService::approveTransaction($this->approvingTxId, $user);
            $this->closeApproveModal();
            $this->dispatch('notify', 'Pengajuan transaksi berhasil disetujui! Saldo dompet telah diperbarui.');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // --- REJECTION ACTIONS (OWNER / SUPERADMIN) ---

    public function openRejectModal(string $txId)
    {
        $user = Auth::user();
        if (!$user->isOwner && !$user->isSuperadmin) {
            abort(403);
        }

        $this->errorMessage = '';
        $this->resetValidation();
        $this->rejectingTxId = $txId;
        $this->rejectingTx = DivisionSavingTransaction::with(['wallet', 'creator'])->findOrFail($txId);
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectingTxId = null;
        $this->rejectingTx = null;
        $this->rejectionReason = '';
        $this->errorMessage = '';
    }

    public function executeReject()
    {
        $user = Auth::user();
        if (!$user->isOwner && !$user->isSuperadmin) {
            abort(403);
        }

        $this->validate([
            'rejectionReason' => 'required|string|min:3|max:500',
        ]);

        $this->errorMessage = '';
        try {
            DivisionSavingService::rejectTransaction($this->rejectingTxId, $user, $this->rejectionReason);
            $this->closeRejectModal();
            $this->dispatch('notify', 'Pengajuan transaksi berhasil ditolak.');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // --- CANCEL TRANSACTION (ADMIN / PEMBUAT PENGAJUAN) ---

    public function cancelTransaction(string $txId)
    {
        $this->errorMessage = '';
        try {
            DivisionSavingService::cancelTransaction($txId, Auth::user());
            $this->dispatch('notify', 'Pengajuan transaksi berhasil dibatalkan.');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // --- WALLET EDITING ---

    public function openEditWalletModal(string $walletId)
    {
        $user = Auth::user();
        $wallet = DivisionWallet::findOrFail($walletId);
        $this->validateDivisionAccess($wallet->division_id);

        $this->editingWalletId = $wallet->id;
        $this->editingWalletTitle = $wallet->title;
        $this->editingWalletDescription = $wallet->description ?? '';
        $this->showEditWalletModal = true;
    }

    public function saveWalletChanges()
    {
        $this->validate([
            'editingWalletTitle' => 'required|string|min:2|max:100',
            'editingWalletDescription' => 'nullable|string|max:255',
        ]);

        $wallet = DivisionWallet::findOrFail($this->editingWalletId);
        $this->validateDivisionAccess($wallet->division_id);

        $wallet->title = trim($this->editingWalletTitle);
        $wallet->description = trim($this->editingWalletDescription);
        $wallet->updated_by = Auth::id();
        $wallet->save();

        $this->showEditWalletModal = false;
        $this->dispatch('notify', 'Data dompet divisi berhasil diperbarui!');
    }

    // --- PROOF VIEWER ---

    public function openProofModal(string $txId)
    {
        $tx = DivisionSavingTransaction::with('wallet')->findOrFail($txId);
        $this->validateDivisionAccess($tx->division_id);

        if (!$tx->proof_attachment_path) {
            return;
        }

        $this->selectedProofUrl = asset('storage/' . $tx->proof_attachment_path);
        $this->selectedProofTitle = "Bukti: {$tx->transaction_code} - {$tx->wallet->title}";
        $this->showProofModal = true;
    }

    public function closeProofModal()
    {
        $this->showProofModal = false;
        $this->selectedProofUrl = null;
        $this->selectedProofTitle = null;
    }

    protected function validateDivisionAccess(int $divisionId): void
    {
        $user = Auth::user();
        if ($user->isOwner || $user->isSuperadmin) {
            return;
        }

        if (!$user->hasDivisionAccess($divisionId)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses ke divisi ini.');
        }
    }

    public function render()
    {
        $user = Auth::user();
        $isOwnerOrSuperadmin = $user->isOwner || $user->isSuperadmin;

        // Resolve accessible divisions list
        $divisions = $isOwnerOrSuperadmin
            ? Division::orderBy('name')->get()
            : $user->getAccessibleDivisions();

        // Ensure active division is valid
        $activeDivId = (int) ($isOwnerOrSuperadmin ? ($this->selectedDivisionId ?: $divisions->first()?->id) : $user->division_id);

        // Fetch summary metrics (only approved balances)
        $summary = DivisionSavingService::getDivisionSummary($activeDivId, $this->selectedMonth ?: null);

        // Fetch all wallets for this division
        $wallets = DivisionWallet::where('division_id', $activeDivId)
            ->where('is_active', true)
            ->withCount([
                'transactions as total_transactions_count',
                'transactions as approved_transactions_count' => fn($q) => $q->where('status', 'approved'),
                'transactions as pending_transactions_count' => fn($q) => $q->where('status', 'pending'),
            ])
            ->orderBy('title')
            ->get();

        // Form wallets (for modal select)
        $formWallets = DivisionWallet::where('division_id', $this->form_division_id ?: $activeDivId)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        // Status counts for tabs
        $statusCounts = [
            'all' => DivisionSavingTransaction::where('division_id', $activeDivId)
                ->when($this->selectedMonth, function ($q) {
                    $start = Carbon::parse($this->selectedMonth . '-01')->startOfMonth()->toDateString();
                    $end = Carbon::parse($this->selectedMonth . '-01')->endOfMonth()->toDateString();
                    $q->whereBetween('transaction_date', [$start, $end]);
                })->count(),
            'pending' => DivisionSavingTransaction::where('division_id', $activeDivId)
                ->where('status', 'pending')
                ->count(),
            'approved' => DivisionSavingTransaction::where('division_id', $activeDivId)
                ->where('status', 'approved')
                ->when($this->selectedMonth, function ($q) {
                    $start = Carbon::parse($this->selectedMonth . '-01')->startOfMonth()->toDateString();
                    $end = Carbon::parse($this->selectedMonth . '-01')->endOfMonth()->toDateString();
                    $q->whereBetween('transaction_date', [$start, $end]);
                })->count(),
            'rejected' => DivisionSavingTransaction::where('division_id', $activeDivId)
                ->where('status', 'rejected')
                ->when($this->selectedMonth, function ($q) {
                    $start = Carbon::parse($this->selectedMonth . '-01')->startOfMonth()->toDateString();
                    $end = Carbon::parse($this->selectedMonth . '-01')->endOfMonth()->toDateString();
                    $q->whereBetween('transaction_date', [$start, $end]);
                })->count(),
        ];

        // Transactions Query
        $txQuery = DivisionSavingTransaction::with(['wallet', 'creator', 'approver', 'rejector'])
            ->where('division_id', $activeDivId)
            ->when($this->selectedStatus, fn($q) => $q->where('status', $this->selectedStatus))
            ->when($this->selectedWalletId, fn($q) => $q->where('wallet_id', $this->selectedWalletId))
            ->when($this->selectedType, fn($q) => $q->where('transaction_type', $this->selectedType))
            ->when($this->selectedMonth, function ($q) {
                $start = Carbon::parse($this->selectedMonth . '-01')->startOfMonth()->toDateString();
                $end = Carbon::parse($this->selectedMonth . '-01')->endOfMonth()->toDateString();
                $q->whereBetween('transaction_date', [$start, $end]);
            })
            ->when($this->search, function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('transaction_code', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('reference_number', 'like', $term);
                });
            })
            ->orderBy('created_at', 'desc');

        $transactions = $txQuery->paginate(15);

        return view('livewire.admin.division-saving-component', [
            'divisions' => $divisions,
            'wallets' => $wallets,
            'formWallets' => $formWallets,
            'summary' => $summary,
            'statusCounts' => $statusCounts,
            'transactions' => $transactions,
            'isOwnerOrSuperadmin' => $isOwnerOrSuperadmin,
            'activeDivision' => Division::find($activeDivId),
        ])->layout('layouts.app');
    }
}
