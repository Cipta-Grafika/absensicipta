<?php

namespace App\Livewire\User;

use App\Models\Payroll;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class PayslipComponent extends Component
{
    use WithPagination;

    public $month = '';
    public $password = '';
    public $selectedPayrollId = null;
    public $showPasswordModal = false;

    public function downloadPdf($payrollId)
    {
        $this->selectedPayrollId = $payrollId;
        $this->resetErrorBag();
        $this->password = '';

        // If login password is already cached in session, download immediately
        if (session()->has('login_password')) {
            return redirect()->route('user.payslip.print', $payrollId);
        }

        $this->showPasswordModal = true;
    }

    public function confirmPasswordAndDownload()
    {
        $this->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'Password wajib diisi.',
        ]);

        if (!Hash::check($this->password, auth()->user()->password)) {
            $this->addError('password', 'Password yang Anda masukkan salah atau tidak sesuai dengan akun Anda.');
            return;
        }

        session(['login_password' => encrypt($this->password)]);
        $this->showPasswordModal = false;
        $targetId = $this->selectedPayrollId;
        $this->password = '';
        $this->selectedPayrollId = null;

        return redirect()->route('user.payslip.print', $targetId);
    }

    public function render()
    {
        $query = Payroll::where('employee_id', auth()->id())
            ->where('status', 'paid');

        if (!empty($this->month)) {
            $query->where('period_month', $this->month);
        }

        $payrolls = $query->orderBy('period_month', 'desc')
            ->paginate(6);

        return view('livewire.user.payslip-component', [
            'payrolls' => $payrolls,
        ])->layout('layouts.app');
    }
}
