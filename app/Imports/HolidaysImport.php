<?php

namespace App\Imports;

use App\Models\Division;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class HolidaysImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $name = trim($row['nama_hari_libur'] ?? '');
        $rawDate = trim($row['tanggal'] ?? '');
        $rawType = strtolower(trim($row['tipe_libur'] ?? 'general'));
        $rawDivName = trim($row['nama_divisi'] ?? '');
        $description = trim($row['keterangan'] ?? '');

        if (empty($name) || empty($rawDate)) {
            return null;
        }

        try {
            $date = Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }

        $type = 'general';
        if (str_contains($rawType, 'divis') || str_contains($rawType, 'division')) {
            $type = 'division';
        } elseif (str_contains($rawType, 'kustom') || str_contains($rawType, 'custom')) {
            $type = 'custom';
        }

        $divisionId = null;
        if (!empty($rawDivName) && strtolower($rawDivName) !== 'semua divisi') {
            $div = Division::where('name', 'like', "%{$rawDivName}%")->first();
            $divisionId = $div?->id;
        }

        return Holiday::updateOrCreate(
            [
                'date' => $date,
                'name' => $name,
            ],
            [
                'type' => $type,
                'division_id' => $divisionId,
                'description' => $description ?: null,
                'created_by' => Auth::id(),
            ]
        );
    }

    public function rules(): array
    {
        return [
            'nama_hari_libur' => ['required', 'string'],
            'tanggal' => ['required'],
        ];
    }
}
