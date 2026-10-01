<?php

namespace App\Exports;

use App\Models\Division;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SavingTransactionsExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        public ?int $year = null,
        public ?string $month = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $type = null,
        public ?int $divisionId = null,
        public ?string $employeeId = null,
        public ?string $statusFilter = null,
        public ?string $search = null
    ) {
    }

    public function title(): string
    {
        return 'Mutasi Syirkah';
    }

    public function view(): View
    {
        $query = SavingTransaction::with(['user.division', 'masterSaving', 'approver'])
            ->whereHas('user', function ($q) {
                $q->onlyEmployee();
            });

        // Scope division if admin
        if (auth()->check() && auth()->user()->group === 'admin' && !auth()->user()->isSyirkah && !auth()->user()->isOwner && !auth()->user()->isPayroll) {
            $query->whereHas('user', function ($q) {
                $q->whereIn('division_id', auth()->user()->getAccessibleDivisionIds());
            });
        } elseif ($this->divisionId) {
            $query->whereHas('user', function ($q) {
                $q->where('division_id', $this->divisionId);
            });
        }

        if ($this->employeeId) {
            $query->where('user_id', $this->employeeId);
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', '%' . $search . '%')
                         ->orWhere('nip', 'like', '%' . $search . '%');
                })
                ->orWhereHas('masterSaving', function ($subQ) use ($search) {
                    $subQ->where('savings_name', 'like', '%' . $search . '%');
                });
            });
        }

        if ($this->month) {
            try {
                $date = Carbon::parse($this->month);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {}
        } elseif ($this->year) {
            $query->whereYear('created_at', $this->year);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
        }

        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Summary Calculations based on filter
        $statsQuery = clone $query;
        $creditQuery = (clone $statsQuery)->where('transaction_type', 'deposit');
        $debitQuery = (clone $statsQuery)->where('transaction_type', 'withdrawal');

        if (!$this->statusFilter) {
            $wajibCredit = (float) (clone $creditQuery)->where('status', 'approved')->sum('mandatory_amount');
            $wajibDebit = (float) (clone $debitQuery)->where('status', 'approved')->sum('mandatory_amount');
            $sukarelaCredit = (float) (clone $creditQuery)->where('status', 'approved')->sum('secondary_amount');
            $sukarelaDebit = (float) (clone $debitQuery)->where('status', 'approved')->sum('secondary_amount');

            $creditCount = (clone $creditQuery)->where('status', 'approved')->count();
            $debitCount = (clone $debitQuery)->where('status', 'approved')->count();
        } else {
            $wajibCredit = (float) (clone $creditQuery)->sum('mandatory_amount');
            $wajibDebit = (float) (clone $debitQuery)->sum('mandatory_amount');
            $sukarelaCredit = (float) (clone $creditQuery)->sum('secondary_amount');
            $sukarelaDebit = (float) (clone $debitQuery)->sum('secondary_amount');

            $creditCount = (clone $creditQuery)->count();
            $debitCount = (clone $debitQuery)->count();
        }

        $totalCredit = $wajibCredit + $sukarelaCredit;
        $totalDebit = $wajibDebit + $sukarelaDebit;

        if ($this->type === 'deposit') {
            $totalWajib = $wajibCredit;
            $totalSukarela = $sukarelaCredit;
            $totalMutasi = $totalCredit;
        } elseif ($this->type === 'withdrawal') {
            $totalWajib = $wajibDebit;
            $totalSukarela = $sukarelaDebit;
            $totalMutasi = $totalDebit;
        } else {
            $totalWajib = max(0.0, $wajibCredit - $wajibDebit);
            $totalSukarela = max(0.0, $sukarelaCredit - $sukarelaDebit);
            $totalMutasi = $totalWajib + $totalSukarela;
        }

        // Ordering: newest to oldest as requested by the user
        $transactions = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
        $totalTransactionsCount = $transactions->count();

        // Labels for filter header
        $periodLabel = 'Semua Periode';
        if ($this->month) {
            $periodLabel = Carbon::parse($this->month)->translatedFormat('F Y');
        } elseif ($this->startDate && $this->endDate) {
            $periodLabel = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');
        } elseif ($this->year) {
            $periodLabel = 'Tahun ' . $this->year;
        }

        $divisionLabel = 'Semua Divisi';
        if ($this->divisionId) {
            $divisionLabel = Division::find($this->divisionId)?->name ?? 'Divisi ID ' . $this->divisionId;
        }

        $statusLabel = match ($this->statusFilter) {
            'approved' => 'Disetujui (Approved)',
            'pending' => 'Menunggu Persetujuan (Pending)',
            'rejected' => 'Ditolak (Rejected)',
            default => 'Semua Status'
        };

        $typeLabel = match ($this->type) {
            'deposit' => 'Setoran (Deposit)',
            'withdrawal' => 'Penarikan (Withdrawal)',
            default => 'Semua Transaksi'
        };

        return view('exports.saving-transactions', [
            'transactions' => $transactions,
            'totalWajib' => $totalWajib,
            'wajibCredit' => $wajibCredit,
            'wajibDebit' => $wajibDebit,
            'totalSukarela' => $totalSukarela,
            'sukarelaCredit' => $sukarelaCredit,
            'sukarelaDebit' => $sukarelaDebit,
            'totalMutasi' => $totalMutasi,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'creditCount' => $creditCount,
            'debitCount' => $debitCount,
            'totalTransactionsCount' => $totalTransactionsCount,
            'periodLabel' => $periodLabel,
            'divisionLabel' => $divisionLabel,
            'statusLabel' => $statusLabel,
            'typeLabel' => $typeLabel,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
            ],
            6 => [
                'font' => ['bold' => true, 'size' => 11],
            ],
            7 => [
                'font' => ['bold' => true, 'size' => 13],
            ],
            10 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            ],
        ];
    }
}
