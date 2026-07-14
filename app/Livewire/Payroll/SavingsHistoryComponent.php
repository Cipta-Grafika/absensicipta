<?php

namespace App\Livewire\Payroll;

use App\Models\SavingsHistory;
use Livewire\Component;
use Livewire\WithPagination;

class SavingsHistoryComponent extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $histories = SavingsHistory::with(['user', 'savings'])
            ->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
            })
            ->orWhereHas('savings', function($q) {
                $q->where('savings_name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(15);

        return view('livewire.payroll.savings-history-component', [
            'histories' => $histories
        ])->layout('layouts.app');
    }
}
