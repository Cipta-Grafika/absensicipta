<?php

namespace App\Livewire\Payroll;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Division;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class PaymentMethodComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $search = '';
    public $division = '';
    public $isModalOpen = false;

    // Selected Employee Context
    public $selectedUser = null;
    public $user_id;
    public $payment_id;

    // Form fields
    public $payment_name = '';
    public $bank_account = '';
    public $account_name = '';

    protected function rules()
    {
        return [
            'user_id'      => 'required|exists:users,id',
            'payment_name' => 'required|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
        ];
    }

    protected function messages()
    {
        return [
            'user_id.required'      => 'Pilihan karyawan wajib terisi.',
            'payment_name.required' => 'Nama Metode / Bank wajib diisi.',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDivision()
    {
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->reset(['selectedUser', 'user_id', 'payment_id', 'payment_name', 'bank_account', 'account_name']);
        $this->resetValidation();
    }

    public function edit(string $userId)
    {
        $this->resetValidation();
        $user = User::with(['paymentMethod', 'division', 'jobTitle'])->findOrFail($userId);
        
        $this->selectedUser = $user;
        $this->user_id = $user->id;

        if ($user->paymentMethod) {
            $this->payment_id = $user->paymentMethod->id;
            $this->payment_name = $user->paymentMethod->payment_name;
            $this->bank_account = $user->paymentMethod->bank_account;
            $this->account_name = $user->paymentMethod->account_name;
        } else {
            $this->payment_id = null;
            $this->payment_name = '';
            $this->bank_account = '';
            $this->account_name = $user->name; // Smart default: employee's full name
        }

        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        PaymentMethod::updateOrCreate(
            ['user_id' => $this->user_id],
            [
                'payment_name' => $this->payment_name,
                'bank_account' => $this->bank_account,
                'account_name' => $this->account_name,
            ]
        );

        $this->banner('Metode pembayaran karyawan berhasil disimpan.');
        $this->closeModal();
    }

    public function removePaymentMethod()
    {
        if ($this->user_id) {
            PaymentMethod::where('user_id', $this->user_id)->delete();
            $this->banner('Metode pembayaran karyawan berhasil direset / dihapus.');
        }

        $this->closeModal();
    }

    public $status = '';

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);

        $employees = User::where('group', '!=', 'superadmin')
            ->when($this->status, function ($query) {
                if ($this->status === 'all') {
                    return $query;
                }
                return $query->where('status', $this->status);
            }, function ($query) {
                // By default display all registered working employees
                return $query->where(function ($q) {
                    $q->whereNull('status')
                      ->orWhereNotIn('status', ['fired', 'resign']);
                });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nip', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhereHas('paymentMethod', function ($subQ) {
                          $subQ->where('payment_name', 'like', '%' . $this->search . '%')
                               ->orWhere('bank_account', 'like', '%' . $this->search . '%')
                               ->orWhere('account_name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->division, function ($query) {
                $query->where('division_id', $this->division);
            })
            ->with(['paymentMethod', 'division', 'jobTitle'])
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.payroll.payment-method-component', [
            'employees' => $employees,
            'divisions' => Division::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
