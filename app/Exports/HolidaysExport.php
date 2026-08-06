<?php

namespace App\Exports;

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HolidaysExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public ?int $year = null;
    public ?string $month = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $type = null;
    public ?int $divisionId = null;

    public function __construct(
        ?int $year = null,
        ?string $month = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $type = null,
        ?int $divisionId = null
    ) {
        $this->year = $year;
        $this->month = $month;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->divisionId = $divisionId;
    }

    public function collection()
    {
        $query = Holiday::with('division')->orderBy('date', 'desc');

        if ($this->year) {
            $query->whereYear('date', $this->year);
        }

        if ($this->month) {
            $date = Carbon::parse($this->month);
            $query->whereYear('date', $date->year)->whereMonth('date', $date->month);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->divisionId) {
            $query->where('division_id', $this->divisionId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'nama_hari_libur',
            'tanggal',
            'tipe_libur',
            'nama_divisi',
            'keterangan',
        ];
    }

    public function map($holiday): array
    {
        $typeLabel = match ($holiday->type) {
            'general' => 'Nasional / Umum',
            'division' => 'Divisi',
            'custom' => 'Kustom Karyawan',
            default => $holiday->type,
        };

        return [
            $holiday->name,
            $holiday->date ? Carbon::parse($holiday->date)->format('Y-m-d') : '',
            $typeLabel,
            $holiday->division?->name ?? 'Semua Divisi',
            $holiday->description ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
        ];
    }
}
