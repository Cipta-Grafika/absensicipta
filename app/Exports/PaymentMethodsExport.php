<?php

namespace App\Exports;

use App\Models\PaymentMethod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentMethodsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return PaymentMethod::with('user')->get();
    }

    public function headings(): array
    {
        return [
            'employee_nip',
            'payment_name',
            'bank_account',
            'account_name',
        ];
    }

    public function map($paymentMethod): array
    {
        return [
            $paymentMethod->user->nip ?? '',
            $paymentMethod->payment_name,
            $paymentMethod->bank_account,
            $paymentMethod->account_name,
        ];
    }
}
