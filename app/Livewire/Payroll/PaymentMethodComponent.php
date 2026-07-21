<?php

namespace App\Livewire\Payroll;

use App\Models\PaymentMethod;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class PaymentMethodComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $search = '';
    public $division = '';
    public $isModalOpen = false;
    public $isConfirmingDeletion = false;

    // Form fields
    public $payment_id;
    public $user_id;
    public $payment_name;
    public $bank_account;
    public $account_name;
    
    protected function rules()
    {
        return [
            'user_id'      => 'required|exists:users,id|unique:payment_methods,user_id,' . $this->payment_id,
            'payment_name' => 'required|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
        ];
    }

    protected function messages()
    {
        return [
            'user_id.unique' => 'Karyawan ini sudah memiliki metode pembayaran yang terdaftar.',
            'user_id.required' => 'Pilihan karyawan wajib diisi.',
            'payment_name.required' => 'Nama Metode/Bank wajib diisi.',
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

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['payment_id', 'user_id', 'payment_name', 'bank_account', 'account_name']);
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->isConfirmingDeletion = false;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $payment = PaymentMethod::with('user')->findOrFail($id);
        
        $this->payment_id = $payment->id;
        $this->user_id = $payment->user_id;
        $this->payment_name = $payment->payment_name;
        $this->bank_account = $payment->bank_account;
        $this->account_name = $payment->account_name;

        $this->isModalOpen = true;

        if ($payment->user) {
            $this->dispatch('set-tomselect-option-user_id', [
                'id' => $payment->user->id,
                'name' => $payment->user->name,
                'nip' => $payment->user->nip,
            ]);
        }
    }

    public function save()
    {
        $this->validate();

        PaymentMethod::updateOrCreate(
            ['id' => $this->payment_id],
            [
                'user_id' => $this->user_id,
                'payment_name' => $this->payment_name,
                'bank_account' => $this->bank_account,
                'account_name' => $this->account_name,
            ]
        );

        $this->banner($this->payment_id ? 'Metode pembayaran berhasil diperbarui.' : 'Metode pembayaran berhasil ditambahkan.');
        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        $this->payment_id = $id;
        $this->isConfirmingDeletion = true;
    }

    public function cancelDelete()
    {
        $this->isConfirmingDeletion = false;
        $this->reset(['payment_id']);
    }

    public function delete()
    {
        if ($this->payment_id) {
            PaymentMethod::findOrFail($this->payment_id)->delete();
            $this->banner('Metode pembayaran berhasil dihapus.');
        }

        $this->closeModal();
    }

    public function render()
    {
        $methods = PaymentMethod::with('user')
            ->where(function ($query) {
                $query->where('payment_name', 'like', '%' . $this->search . '%')
                      ->orWhere('bank_account', 'like', '%' . $this->search . '%')
                      ->orWhere('account_name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->division, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('division_id', $this->division);
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.payroll.payment-method-component', [
            'methods' => $methods
        ])->layout('layouts.app');
    }
}
