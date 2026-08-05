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
