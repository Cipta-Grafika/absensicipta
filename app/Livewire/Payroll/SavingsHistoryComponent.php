<?php

namespace App\Livewire\Payroll;

use App\Models\SavingsHistory;
use Livewire\Component;
use Livewire\WithPagination;

class SavingsHistoryComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $month = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMonth()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = SavingsHistory::with(['user', 'savings'])
            ->whereHas('user', function ($q) {
                $q->onlyEmployee();
            });

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('user', function($subQ) {
                    $subQ->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('nip', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('savings', function($subQ) {
                    $subQ->where('savings_name', 'like', '%' . $this->search . '%');
                });
            });
        }

        if ($this->month) {
            try {
                $date = \Carbon\Carbon::parse($this->month);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        $histories = $query->latest()->paginate(15);

        return view('livewire.payroll.savings-history-component', [
            'histories' => $histories
        ])->layout('layouts.app');
    }
}
