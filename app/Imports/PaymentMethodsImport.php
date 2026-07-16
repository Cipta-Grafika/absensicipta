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
        $user = User::where('nip', $row['employee_nip'])->first();
        if (!$user) {
            return null; // Skip if user not found
        }

        $paymentMethod = PaymentMethod::firstOrNew(['user_id' => $user->id]);

        $paymentMethod->forceFill([
            'payment_name' => $row['payment_name'],
            'bank_account' => $row['bank_account'],
            'account_name' => $row['account_name'],
        ]);

        if ($this->save) {
            $paymentMethod->save();
        }

        $paymentMethod->setRelation('user', $user);

        return $paymentMethod;
    }

    public function rules(): array
    {
        return [
            'employee_nip' => ['required', 'exists:users,nip'],
            'payment_name' => ['required'],
            'bank_account' => ['required'],
            'account_name' => ['required'],
        ];
    }

    public function onFailure(Failure ...$failures)
    {
    }
}
