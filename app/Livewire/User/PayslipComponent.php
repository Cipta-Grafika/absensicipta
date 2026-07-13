<?php

namespace App\Livewire\User;

use App\Models\Payroll;
use Livewire\Component;
use Livewire\WithPagination;

class PayslipComponent extends Component
{
    use WithPagination;

    public function render()
    {
        $payrolls = Payroll::where('employee_id', auth()->id())
            ->where('status', 'paid')
            ->orderBy('period_month', 'desc')
            ->paginate(12);

        return view('livewire.user.payslip-component', [
            'payrolls' => $payrolls,
        ])->layout('layouts.app');
    }
}
