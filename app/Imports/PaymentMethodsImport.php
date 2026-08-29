<?php

namespace App\Imports;

use App\Models\PaymentMethod;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class PaymentMethodsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public function __construct(public bool $save = true)
    {
    }

    public function model(array $row)
    {
        $nip = $row['employee_nip'] ?? $row['nip'] ?? null;
        if (!$nip) {
            return null; // Skip if NIP is missing
        }

        $user = User::onlyWorkingEmployee()->where('nip', $nip)->first();
        if (!$user) {
            return null; // Skip if user not found
        }

        $paymentName = !empty($row['payment_name']) ? trim($row['payment_name']) : 'CASH';
        $bankAccount = !empty($row['bank_account']) ? trim($row['bank_account']) : null;
        $accountName = !empty($row['account_name']) ? trim($row['account_name']) : $user->name;

        if ($this->save) {
            // Delete any existing duplicate payment methods for this user to enforce 1 method per employee
            PaymentMethod::where('user_id', $user->id)->delete();

            $paymentMethod = PaymentMethod::create([
                'user_id'      => $user->id,
                'payment_name' => $paymentName,
                'bank_account' => $bankAccount,
                'account_name' => $accountName,
            ]);
        } else {
            $paymentMethod = new PaymentMethod([
                'user_id'      => $user->id,
                'payment_name' => $paymentName,
                'bank_account' => $bankAccount,
                'account_name' => $accountName,
            ]);
        }

        $paymentMethod->setRelation('user', $user);

        return $paymentMethod;
    }

    public function rules(): array
    {
        return [
            'employee_nip' => ['required_without:nip'],
            'nip'          => ['required_without:employee_nip'],
            'payment_name' => ['nullable'],
            'bank_account' => ['nullable'],
            'account_name' => ['nullable'],
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        $messages = [];
        foreach ($failures as $failure) {
            $messages[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
        }
        throw new \Exception(implode('<br>', $messages));
    }
}

