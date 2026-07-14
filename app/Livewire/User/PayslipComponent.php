<?php

namespace App\Livewire\User;

use App\Models\Payroll;
use Livewire\Component;
use Livewire\WithPagination;

class PayslipComponent extends Component
{
    use WithPagination;

    public $month = '';

    public function render()
    {
        $query = Payroll::where('employee_id', auth()->id())
            ->where('status', 'paid');

        if (!empty($this->month)) {
            $query->where('period_month', $this->month);
        }

        $payrolls = $query->orderBy('period_month', 'desc')
            ->orderBy('period_month', 'desc')
            ->paginate(6);

        return view('livewire.user.payslip-component', [
            'payrolls' => $payrolls,
        ])->layout('layouts.app');
    }
}
