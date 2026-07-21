<?php

namespace App\Imports;

use App\Models\Saving;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class SavingsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public function __construct(public bool $save = true)
    {
    }

    public function model(array $row)
    {
        $savingsName = $row['savings_name'] ?? null;
        
        if (!$savingsName) {
            return null;
        }

        $saving = Saving::firstOrNew(['savings_name' => $savingsName]);

        $saving->forceFill([
            'savings_name' => $row['savings_name'],
            'mandatory_savings' => $row['mandatory_savings'],
            'secondary_savings' => $row['secondary_savings'],
        ]);

        if ($this->save) {
            $saving->save();
        }

        return $saving;
    }

    public function rules(): array
    {
        return [
            'savings_name' => ['required'],
            'mandatory_savings' => ['required', 'numeric'],
            'secondary_savings' => ['required', 'numeric'],
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
