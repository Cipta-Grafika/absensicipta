<?php

namespace App\Exports;

use App\Models\Overtime;
use App\Models\OvertimeRate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OvertimesExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?string $month = null,
        protected ?string $year = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        protected ?string $division = null,
        protected ?string $jobTitle = null,
        protected ?string $status = 'approved'
    ) {
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama Karyawan',
            'Divisi',
            'Jabatan',
            'Tanggal Lembur',
            'Jam Mulai',
            'Jam Selesai',
            'Istirahat',
            'Durasi (Jam)',
            'Tarif / Jam (Rp)',
            'Uang Makan (Rp)',
            'Total Bayar (Rp)',
            'Status',
            'Disetujui Oleh',
            'Tanggal Persetujuan',
            'Tanggal Dibayar',
            'Alasan / Keterangan'
        ];
    }

    public function getQuery(): Builder
    {
        $query = Overtime::with(['employee.division', 'employee.jobTitle', 'employee.salary', 'approver'])
            ->orderBy('overtime_date', 'asc')
            ->orderBy('id', 'asc');

        if (!empty($this->status) && $this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            $query->whereBetween('overtime_date', [
                Carbon::parse($this->dateFrom)->format('Y-m-d'),
                Carbon::parse($this->dateTo)->format('Y-m-d')
            ]);
        } elseif (!empty($this->dateFrom)) {
            $query->where('overtime_date', Carbon::parse($this->dateFrom)->format('Y-m-d'));
        } elseif (!empty($this->month)) {
            $date = Carbon::parse($this->month);
            $query->whereMonth('overtime_date', $date->month)
                  ->whereYear('overtime_date', $date->year);
        } elseif (!empty($this->year)) {
            $query->whereYear('overtime_date', $this->year);
        }

        if (!empty($this->division)) {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('division_id', $this->division);
            });
        }

        if (!empty($this->jobTitle)) {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('job_title_id', $this->jobTitle);
            });
        }

        return $query;
    }

    public function array(): array
    {
        $overtimes = $this->getQuery()->get();
        $rows = [];
        $no = 1;

        foreach ($overtimes as $ot) {
            $emp = $ot->employee;
            $durationHours = (float)($ot->duration_hours ?? $ot->calculateDuration());
            $totalPay = (float)($ot->total_pay ?? $ot->overtime_pay ?? 0);

            $payCalc = OvertimeRate::calculatePayForDuration($durationHours, $emp, $ot->start_time, $ot->end_time, $ot->overtime_date ? $ot->overtime_date->format('Y-m-d') : null);
            $mealPay = (float)($payCalc['meal_allowance'] ?? 0);
            $rateAmount = (float)($ot->applied_rate_amount ?? $payCalc['applied_rate_amount'] ?? 0);

            $statusLabel = match ($ot->status) {
                'approved' => 'Disetujui',
                'paid' => 'Sudah Dibayar',
                'rejected' => 'Ditolak',
                'pending' => 'Menunggu',
                default => ucfirst($ot->status),
            };

            $rows[] = [
                $no++,
                $emp?->nip ?? '-',
                $emp?->name ?? '-',
                $emp?->division?->name ?? '-',
                $emp?->jobTitle?->name ?? '-',
                $ot->overtime_date ? Carbon::parse($ot->overtime_date)->format('d/m/Y') : '-',
                $ot->start_time ? Carbon::parse($ot->start_time)->format('H:i') : '-',
                $ot->end_time ? Carbon::parse($ot->end_time)->format('H:i') : '-',
                $ot->break ?: '-',
                $durationHours,
                $rateAmount,
                $mealPay,
                $totalPay,
                $statusLabel,
                $ot->approver?->name ?? '-',
                $ot->approval_date ? Carbon::parse($ot->approval_date)->format('d/m/Y H:i') : '-',
                $ot->paid_at ? Carbon::parse($ot->paid_at)->format('d/m/Y H:i') : '-',
                $ot->reason ?? '-'
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0284C7'] // Sky-600 Blue
                ]
            ],
        ];
    }
}
