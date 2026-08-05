<?php

namespace App\Livewire\Admin\ImportExport;

use App\Exports\WorkSchedulesExport;
use App\Imports\WorkSchedulesImport;
use App\Models\Division;
use App\Models\WorkSchedule as WorkScheduleModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class WorkSchedule extends Component
{
    use InteractsWithBanner, WithFileUploads;

    public bool $previewing = false;
    public ?string $mode = null;
    public $file = null;
    public $year = null;
    public $month = null;
    public $division = null;
    public $start_date = null;
    public $end_date = null;

    protected $rules = [
        'file' => 'required|mimes:csv,xls,xlsx,ods',
        'year' => 'nullable|date_format:Y',
        'month' => 'nullable|date_format:Y-m',
        'division' => 'nullable|exists:divisions,id',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date',
    ];

    public function preview()
    {
        $this->previewing = !$this->previewing;
        $this->mode = $this->previewing ? 'export' : null;
    }

    public function mount()
    {
        $this->year = date('Y');
    }

    public function downloadTemplate()
    {
        $templateData = [
            [
                'nip' => 'EMP-001',
                'nama_karyawan' => 'Budi Santoso',
                'tanggal' => date('Y-m-d'),
                'status' => 'Hari Kerja',
                'divisi' => 'Teknologi Informasi',
                'catatan' => 'Piket Shift Pagi',
            ],
            [
                'nip' => 'EMP-002',
                'nama_karyawan' => 'Siti Rahma',
                'tanggal' => date('Y-m-d'),
                'status' => 'Hari Libur',
                'divisi' => 'Teknologi Informasi',
                'catatan' => 'Day Off Roster',
            ],
        ];

        return Excel::download(new class($templateData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\ShouldAutoSize {
            public function __construct(private array $data) {}
            public function array(): array { return $this->data; }
            public function headings(): array { return ['nip', 'nama_karyawan', 'tanggal', 'status', 'divisi', 'catatan']; }
        }, 'template_import_jadwal_rolling.xlsx');
    }

    public function render()
    {
        $schedules = null;
        if ($this->file) {
            $this->mode = 'import';
            $this->previewing = true;
            try {
                $importHandler = new WorkSchedulesImport(save: false);
                $schedules = Excel::toCollection($importHandler, $this->file)
                    ->first()
                    ->map(function (\Illuminate\Support\Collection $row) use ($importHandler) {
                        return $importHandler->model($row->toArray());
                    });
            } catch (\Throwable $th) {
                $this->dangerBanner($th->getMessage());
                $schedules = collect();
            }
        } elseif ($this->previewing && $this->mode === 'export') {
            $user = Auth::user();
            $divisionId = !$user->isSuperadmin ? $user->division_id : $this->division;
            
            $query = WorkScheduleModel::with(['user.division']);
            if ($divisionId) {
                $query->whereHas('user', fn($u) => $u->where('division_id', $divisionId));
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
        } else {
            $this->previewing = false;
            $this->mode = null;
        }

        return view('livewire.admin.import-export.work-schedule', [
            'schedules' => $schedules,
            'divisions' => Auth::user()->isSuperadmin ? Division::orderBy('name')->get() : collect(),
        ]);
    }

    public function import()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        try {
            $this->validate(['file' => 'required|mimes:csv,xls,xlsx,ods']);
            Excel::import(new WorkSchedulesImport(save: true), $this->file);

            $this->banner(__('Data jadwal rolling berhasil diimpor!'));
            $this->reset(['file', 'previewing', 'mode']);
        } catch (\Throwable $th) {
            $this->dangerBanner($th->getMessage());
        }
    }

    public function export()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $user = Auth::user();
        $divisionId = !$user->isSuperadmin ? $user->division_id : $this->division;
        $division = $divisionId ? Division::find($divisionId)?->name : null;

        $filename = 'jadwal_rolling' . ($this->month ? '_' . Carbon::parse($this->month)->format('F-Y') : '') . ($this->year && !$this->month ? '_' . $this->year : '') . ($division ? '_' . Str::slug($division) : '') . '.xlsx';

        return Excel::download(new WorkSchedulesExport(
            $this->month,
            $this->year,
            $divisionId,
            $this->start_date,
            $this->end_date
        ), $filename);
    }
}
