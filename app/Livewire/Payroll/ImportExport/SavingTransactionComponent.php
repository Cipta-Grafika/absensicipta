<?php

namespace App\Livewire\Payroll\ImportExport;

use App\Exports\SavingTransactionsExport;
use App\Imports\SavingTransactionsImport;
use App\Models\Division;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Services\SavingTransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class SavingTransactionComponent extends Component
{
    use InteractsWithBanner, WithFileUploads;

    public $file = null;
    public ?int $year = null;
    public ?string $month = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $type = null;
    public ?int $division = null;
    public ?string $employee_id = null;

    public string $mode = '';
    public bool $previewing = false;

    public function mount()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin, 403);

        $this->year = (int) date('Y');
        $this->month = date('Y-m');
    }

    public function export()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin, 403);

        $filename = 'Export_Mutasi_Syirkah_' . date('Ymd_His') . '.xlsx';
        return Excel::download(
            new SavingTransactionsExport(
                $this->year,
                $this->month,
                $this->start_date,
                $this->end_date,
                $this->type,
                $this->division,
                $this->employee_id
            ),
            $filename
        );
    }

    public function preview()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin, 403);

        if ($this->mode === 'export') {
            $this->previewing = false;
            $this->mode = '';
            return;
        }

        $this->mode = 'export';
        $this->previewing = true;
    }

    public function downloadTemplate()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin, 403);

        $sampleUser = User::where('group', 'user')->first();
        $sampleSaving = Saving::first();

        $sampleData = collect([
            [
                'nip' => $sampleUser?->nip ?? '123124234',
                'nama_karyawan' => $sampleUser?->name ?? 'Zaenal Alfian',
                'nama_syirkah' => $sampleSaving?->savings_name ?? 'Syirkah Full',
                'tanggal' => date('Y-m') . '-01',
                'tipe_transaksi' => 'deposit',
                'mutasi_wajib' => 500000,
                'mutasi_sukarela' => 250000,
                'keterangan' => 'Saldo Awal Syirkah (Migrasi Data Lama)',
            ],
            [
                'nip' => $sampleUser?->nip ?? '123124234',
                'nama_karyawan' => $sampleUser?->name ?? 'Zaenal Alfian',
                'nama_syirkah' => $sampleSaving?->savings_name ?? 'Syirkah Full',
                'tanggal' => date('Y-m') . '-15',
                'tipe_transaksi' => 'deposit',
                'mutasi_wajib' => 100000,
                'mutasi_sukarela' => 50000,
                'keterangan' => 'Potongan Syirkah Payroll ' . date('Y-m'),
            ],
            [
                'nip' => $sampleUser?->nip ?? '123124234',
                'nama_karyawan' => $sampleUser?->name ?? 'Zaenal Alfian',
                'nama_syirkah' => $sampleSaving?->savings_name ?? 'Syirkah Full',
                'tanggal' => date('Y-m') . '-20',
                'tipe_transaksi' => 'withdrawal',
                'mutasi_wajib' => 0,
                'mutasi_sukarela' => 100000,
                'keterangan' => 'Pencairan Syirkah Sukarela',
            ],
        ]);

        return Excel::download(
            new class($sampleData) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\ShouldAutoSize, \Maatwebsite\Excel\Concerns\WithStyles {
                public function __construct(public $data) {}
                public function collection() { return $this->data; }
                public function headings(): array {
                    return ['nip', 'nama_karyawan', 'nama_syirkah', 'tanggal', 'tipe_transaksi', 'mutasi_wajib', 'mutasi_sukarela', 'keterangan'];
                }
                public function map($row): array {
                    return [
                        $row['nip'],
                        $row['nama_karyawan'],
                        $row['nama_syirkah'],
                        $row['tanggal'],
                        $row['tipe_transaksi'],
                        $row['mutasi_wajib'],
                        $row['mutasi_sukarela'],
                        $row['keterangan'],
                    ];
                }
                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
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
            },
            'Template_Import_Mutasi_Syirkah.xlsx'
        );
    }

    public function import()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin, 403);

        $this->validate([
            'file' => ['required', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            Excel::import(new SavingTransactionsImport, $this->file->getRealPath());
            $this->banner(__('Data mutasi syirkah berhasil diimpor & saldo berjalan dihitung ulang.'));
            $this->reset(['file', 'mode', 'previewing']);
        } catch (\Throwable $e) {
            $this->dangerBanner(__('Gagal mengimpor file: ') . $e->getMessage());
        }
    }

    public function render()
    {
        abort_unless(Auth::user()?->isPayroll || Auth::user()?->isSyirkah || Auth::user()?->isSuperadmin, 403);

        $query = SavingTransaction::with(['user.division', 'masterSaving'])
            ->whereHas('user', function ($q) {
                $q->onlyEmployee();
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($this->year) {
            $query->whereYear('created_at', $this->year);
        }
        if ($this->month) {
            $query->where('period_month', $this->month);
        }
        if ($this->start_date && $this->end_date) {
            $query->whereBetween('created_at', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        }
        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }
        if ($this->division) {
            $query->whereHas('user', function ($q) {
                $q->where('division_id', $this->division);
            });
        }
        if ($this->employee_id) {
            $query->where('user_id', $this->employee_id);
        }

        $transactions = $query->take(50)->get();

        return view('livewire.payroll.import-export.saving-transaction', [
            'transactions' => $transactions,
            'divisions' => Division::orderBy('name')->get(),
            'users' => User::onlyWorkingEmployee()
                ->orderBy('name')
                ->get(),
        ]);
    }
}
