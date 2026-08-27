<?php

namespace App\Exports;

use App\Models\SavingTransaction;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SavingTransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public ?int $year = null;
    public ?string $month = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $type = null;
    public ?int $divisionId = null;
    public ?string $employeeId = null;

    public function __construct(
        ?int $year = null,
        ?string $month = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $type = null,
        ?int $divisionId = null,
        ?string $employeeId = null
    ) {
        $this->year = $year;
        $this->month = $month;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->divisionId = $divisionId;
        $this->employeeId = $employeeId;
    }

    public function collection()
    {
        $query = SavingTransaction::with(['user.division', 'masterSaving'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        if ($this->year) {
            $query->whereYear('created_at', $this->year);
        }

        if ($this->month) {
            $date = Carbon::parse($this->month);
            $query->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
        }

        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }

        if ($this->divisionId) {
            $query->whereHas('user', function ($q) {
                $q->where('division_id', $this->divisionId);
            });
        }

        if ($this->employeeId) {
            $query->where('user_id', $this->employeeId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'nip',
            'nama_karyawan',
            'nama_syirkah',
            'tanggal',
            'tipe_transaksi',
            'mutasi_wajib',
            'mutasi_sukarela',
            'saldo_wajib',
            'saldo_sukarela',
            'status',
            'disetujui_oleh',
            'keterangan',
        ];
    }

    public function map($tx): array
    {
        return [
            $tx->user?->nip ?? '-',
            $tx->user?->name ?? '-',
            $tx->masterSaving?->savings_name ?? 'Syirkah Full',
            $tx->created_at ? Carbon::parse($tx->created_at)->format('Y-m-d H:i') : '',
            $tx->transaction_type === 'withdrawal' ? 'Penarikan (Withdrawal)' : 'Setor (Deposit)',
            (float) $tx->mandatory_amount,
            (float) $tx->secondary_amount,
            (float) $tx->balance_mandatory,
            (float) $tx->balance_secondary,
            strtoupper($tx->status ?? 'APPROVED'),
            $tx->approver?->name ?? '-',
            $tx->description ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
            ],
        ];
    }
}
