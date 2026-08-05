<?php

namespace App\Exports;

use App\Models\WorkSchedule;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkSchedulesExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        public ?string $month = null,
        public ?string $year = null,
        public ?int $division_id = null,
        public ?string $start_date = null,
        public ?string $end_date = null
    ) {}

    public function view(): View
    {
        $query = WorkSchedule::with(['user.division']);

        if (auth()->user()->group === 'admin') {
            $query->whereHas('user', fn($u) => $u->where('division_id', auth()->user()->division_id));
        } elseif ($this->division_id) {
            $query->whereHas('user', fn($u) => $u->where('division_id', $this->division_id));
        }

        if ($this->month) {
            $query->where('date', 'like', $this->month . '%');
        } elseif ($this->year) {
            $query->whereYear('date', $this->year);
        }

        if ($this->start_date) {
            $query->where('date', '>=', $this->start_date);
        }
        if ($this->end_date) {
            $query->where('date', '<=', $this->end_date);
        }

        $schedules = $query->orderBy('date', 'desc')->get();

        return view('admin.import-export.export-work-schedules', [
            'schedules' => $schedules,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
