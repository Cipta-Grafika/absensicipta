<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\Saving;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SavingTransactionComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $month = '';
    public $type = '';
    public $division = '';

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

    public function openEditNominalModal($transactionId)
    {
        if (!auth()->user()?->isSyirkah) {
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
        if (!auth()->user()?->isSyirkah) {
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
            \App\Services\SavingTransactionService::recalculateUserTransactions($tx->user_id);
        });

        $this->closeEditNominalModal();
        $this->dispatch('notify', 'Nominal mutasi syirkah berhasil diperbarui.');
    }

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

    public function updatingDivision()
    {
        $this->resetPage();
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
            // Calculate true balance dynamically for maximum accuracy
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

            $newBalanceMandatory = $balanceMandatory - $withdrawMandatory;
            $newBalanceSecondary = $balanceSecondary - $withdrawSecondary;

            SavingTransaction::create([
                'user_id' => $this->withdrawal_user_id,
                'savings_id' => $this->withdrawal_savings_id,
                'transaction_type' => 'withdrawal',
                'mandatory_amount' => $withdrawMandatory,
                'secondary_amount' => $withdrawSecondary,
                'balance_mandatory' => $newBalanceMandatory,
                'balance_secondary' => $newBalanceSecondary,
                'description' => $this->withdrawal_description ?: 'Pencairan Syirkah',
            ]);

            \App\Services\SavingTransactionService::recalculateUserTransactions($this->withdrawal_user_id, $this->withdrawal_savings_id);

            DB::commit();
            $this->closeWithdrawalModal();
            $this->dispatch('notify', 'Pencairan berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('withdrawal_user_id', 'Gagal memproses pencairan: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = SavingTransaction::with(['user', 'masterSaving']);

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

        if ($this->division) {
            $query->whereHas('user', function($q) {
                $q->where('division_id', $this->division);
            });
        }

        $transactions = $query->latest()->paginate(15);
        $users = User::where('group', 'user')->whereIn('status', ['active', 'suspend'])->orderBy('name')->get();
        $savingsList = Saving::orderBy('savings_name')->get();

        // Calculate dynamic true balance for summary
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
        
        $totalWajib = $summaryQuery->sum('total_mandatory');
        $totalSukarela = $summaryQuery->sum('total_secondary');

        return view('livewire.payroll.saving-transaction-component', [
            'transactions' => $transactions,
            'users' => $users,
            'savingsList' => $savingsList,
            'totalWajib' => $totalWajib,
            'totalSukarela' => $totalSukarela,
        ])->layout('layouts.app');
    }
}
