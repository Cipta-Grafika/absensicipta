<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoanComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $division = '';

    // Modal Create Loan
    public $createModalOpen = false;
    public $user_id = '';
    public $loan_amount = 0;
    public $tenor_months = 1;
    public $description = '';

    // Computed Properties for Loan
    public $installment_amount = 0;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDivision()
    {
        $this->resetPage();
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
            'status' => 'approved', // Langsung disetujui untuk kemudahan (sesuai role Admin/Payroll)
            'description' => $this->description,
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', 'Pinjaman berhasil dibuat.');
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

    public function render()
    {
        $query = Loan::with(['user', 'installments']);

        if ($this->search) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->division) {
            $query->whereHas('user', function($q) {
                $q->where('division_id', $this->division);
            });
        }

        $loans = $query->latest()->paginate(15);
        $users = User::where('group', 'user')->orderBy('name')->get();

        return view('livewire.payroll.loan-component', [
            'loans' => $loans,
            'users' => $users,
        ])->layout('layouts.app');
    }
}
