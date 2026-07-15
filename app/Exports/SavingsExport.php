<?php

namespace App\Exports;

use App\Models\Saving;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SavingsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Saving::all();
    }

    public function headings(): array
    {
        return [
            'savings_name',
            'mandatory_savings',
            'secondary_savings',
        ];
    }

    public function map($saving): array
    {
        return [
            $saving->savings_name,
            $saving->mandatory_savings,
            $saving->secondary_savings,
        ];
    }
}
