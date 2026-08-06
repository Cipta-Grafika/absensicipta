<?php

namespace App\Livewire\Admin\ImportExport;

use App\Exports\HolidaysExport;
use App\Imports\HolidaysImport;
use App\Models\Division;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class HolidayComponent extends Component
{
    use InteractsWithBanner, WithFileUploads;

    public $file = null;
    public ?int $year = null;
    public ?string $month = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $type = null;
    public ?int $division = null;

    public string $mode = '';
    public bool $previewing = false;

    public function mount()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak. Hanya SuperAdmin yang berhak mengelola Impor & Ekspor Hari Libur.');
        }

        $this->year = (int) date('Y');
        $this->month = date('Y-m');
    }

    public function export()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $filename = 'Export_Hari_Libur_' . date('Ymd_His') . '.xlsx';
        return Excel::download(
            new HolidaysExport(
                $this->year,
                $this->month,
                $this->start_date,
                $this->end_date,
                $this->type,
                $this->division
            ),
            $filename
        );
    }

    public function preview()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

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
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $sampleHolidays = collect([
            [
                'name' => 'Tahun Baru Masehi',
                'date' => date('Y') . '-01-01',
                'type' => 'general',
                'division_id' => null,
                'description' => 'Libur Nasional Tahun Baru',
            ],
            [
                'name' => 'Hari Raya Idul Fitri',
                'date' => date('Y') . '-04-10',
                'type' => 'general',
                'division_id' => null,
                'description' => 'Libur Nasional Idul Fitri',
            ],
            [
                'name' => 'HUT Perusahaan Divisi IT',
                'date' => date('Y') . '-08-17',
                'type' => 'division',
                'division_id' => Division::first()?->id,
                'description' => 'Libur Khusus Divisi',
            ],
        ]);

        return Excel::download(
            new class($sampleHolidays) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\ShouldAutoSize {
                public function __construct(public $data) {}
                public function collection() { return $this->data; }
                public function headings(): array {
                    return ['nama_hari_libur', 'tanggal', 'tipe_libur', 'nama_divisi', 'keterangan'];
                }
                public function map($row): array {
                    $divName = $row['division_id'] ? Division::find($row['division_id'])?->name : 'Semua Divisi';
                    $type = match ($row['type']) {
                        'general' => 'Nasional / Umum',
                        'division' => 'Divisi',
                        default => 'General',
                    };
                    return [$row['name'], $row['date'], $type, $divName, $row['description']];
                }
            },
            'Template_Import_Hari_Libur.xlsx'
        );
    }

    public function import()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $this->validate([
            'file' => ['required', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            Excel::import(new HolidaysImport, $this->file->getRealPath());
            $this->banner(__('Data hari libur berhasil diimpor.'));
            $this->reset(['file', 'mode', 'previewing']);
        } catch (\Throwable $e) {
            $this->banner(__('Gagal mengimpor file: ') . $e->getMessage(), 'danger');
        }
    }

    public function render()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $query = Holiday::with('division')->orderBy('date', 'desc');

        if ($this->year) {
            $query->whereYear('date', $this->year);
        }
        if ($this->month) {
            $date = Carbon::parse($this->month);
            $query->whereYear('date', $date->year)->whereMonth('date', $date->month);
        }
        if ($this->start_date && $this->end_date) {
            $query->whereBetween('date', [$this->start_date, $this->end_date]);
        }
        if ($this->type) {
            $query->where('type', $this->type);
        }
        if ($this->division) {
            $query->where('division_id', $this->division);
        }

        $holidays = $query->take(50)->get();

        return view('livewire.admin.import-export.holiday', [
            'holidays' => $holidays,
            'divisions' => Division::orderBy('name')->get(),
        ]);
    }
}
