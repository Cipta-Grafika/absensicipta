<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\DivisionWallet;
use App\Models\DivisionSavingTransaction;
use App\Services\DivisionSavingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
            // Owner defaults to first division or can view all
            $firstDiv = Division::orderBy('name')->first();
            $this->selectedDivisionId = $firstDiv?->id;
            $this->form_division_id = $firstDiv?->id;
        } else {
            // Admin is strictly locked to their division
            $this->selectedDivisionId = $user->division_id;
            $this->form_division_id = $user->division_id;
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

            DivisionSavingService::recordTransaction(
                divisionId: $targetDivId,
                walletOrTitle: $walletOrTitle,
                type: $this->form_type,
                amount: (float) $this->form_amount,
                description: $this->form_description,
                actor: $user,
                transactionDate: $this->form_date,
                referenceNumber: $this->form_reference_number ?: null,
                proofAttachmentPath: $proofPath
            );

            $this->closeTransactionModal();
            $this->dispatch('notify', 'Transaksi tabungan divisi berhasil dicatat!');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

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

        // Fetch summary metrics
        $summary = DivisionSavingService::getDivisionSummary($activeDivId, $this->selectedMonth ?: null);

        // Fetch all wallets for this division
        $wallets = DivisionWallet::where('division_id', $activeDivId)
            ->where('is_active', true)
            ->withCount('transactions')
            ->orderBy('title')
            ->get();

        // Form wallets (for modal select)
        $formWallets = DivisionWallet::where('division_id', $this->form_division_id ?: $activeDivId)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        // Transactions Query
        $txQuery = DivisionSavingTransaction::with(['wallet', 'creator'])
            ->where('division_id', $activeDivId)
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
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $transactions = $txQuery->paginate(15);

        return view('livewire.admin.division-saving-component', [
            'divisions' => $divisions,
            'wallets' => $wallets,
            'formWallets' => $formWallets,
            'summary' => $summary,
            'transactions' => $transactions,
            'isOwnerOrSuperadmin' => $isOwnerOrSuperadmin,
            'activeDivision' => Division::find($activeDivId),
        ])->layout('layouts.app');
    }
}
