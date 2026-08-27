<?php

namespace App\Livewire\Admin\ImportExport;

use App\Exports\OvertimesExport;
use App\Exports\PeachtreeOvertimeExport;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\Overtime as OvertimeModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Overtime extends Component
{
    use InteractsWithBanner;

    // Filters
    public $year = null;
    public $month = null;
    public $date_from = null;
    public $date_to = null;
    public $division = null;
    public $job_title = null;
    public $status = 'approved';
    public $export_format = 'peachtree_csv'; // 'peachtree_csv' or 'excel_xlsx'

    // Peachtree Configuration
    public $cash_account_prefix = '10010';
    public $overtime_account_prefix = '70020';
    public $meal_account_prefix = '70060';
    public $liability_account_prefix = '21120';
    public $expense_account_prefix = '78010';
    public $check_number_mode = 'custom'; // 'custom', 'prefix_id', 'nip'
    public $check_number_custom = '';
    public $division_subaccount_code = '';
    public $transaction_period = '';
    public $pay_method_frequency = '8';

    // UI state
    public bool $previewing = true;
    public string $preview_tab = 'peachtree'; // 'peachtree' or 'data'
    public bool $show_peachtree_settings = false;

    protected $rules = [
        'year' => 'nullable|date_format:Y',
        'month' => 'nullable|date_format:Y-m',
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date',
        'division' => 'nullable|exists:divisions,id',
        'job_title' => 'nullable|exists:job_titles,id',
        'status' => 'nullable|in:all,approved,paid,pending,rejected',
        'export_format' => 'required|in:peachtree_csv,excel_xlsx',
    ];

    public function mount(): void
    {
        $this->year = date('Y');
        $this->month = date('Y-m');

        if (Auth::user()->group === 'admin') {
            $this->division = Auth::user()->division_id;
            if ($this->division) {
                $this->division_subaccount_code = sprintf('%02d', (int)$this->division);
            }
        }
    }

    public function toggleSettings(): void
    {
        $this->show_peachtree_settings = !$this->show_peachtree_settings;
    }

    public function resetFilters(): void
    {
        $this->year = date('Y');
        $this->month = date('Y-m');
        $this->date_from = null;
        $this->date_to = null;
        $this->job_title = null;
        $this->status = 'approved';

        if (Auth::user()->group === 'admin') {
            $this->division = Auth::user()->division_id;
        } else {
            $this->division = null;
        }
    }

    protected function getEffectiveDivisionId()
    {
        if (Auth::user()->group === 'admin') {
            return Auth::user()->division_id;
        }
        return $this->division;
    }

    protected function getPeachtreeConfig(): array
    {
        return [
            'cash_account_prefix' => $this->cash_account_prefix ?: '10010',
            'overtime_account_prefix' => $this->overtime_account_prefix ?: '70020',
            'meal_account_prefix' => $this->meal_account_prefix ?: '70060',
            'liability_account_prefix' => $this->liability_account_prefix ?: '21120',
            'expense_account_prefix' => $this->expense_account_prefix ?: '78010',
            'check_number_mode' => $this->check_number_mode ?: 'custom',
            'check_number_custom' => $this->check_number_custom ?? '',
            'division_subaccount_code' => $this->division_subaccount_code,
            'transaction_period' => $this->transaction_period,
            'pay_method_frequency' => $this->pay_method_frequency ?: '8',
        ];
    }

    public function exportPeachtree()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $divisionId = $this->getEffectiveDivisionId();
        $divisionName = $divisionId ? Division::find($divisionId)?->name : null;

        $export = new PeachtreeOvertimeExport(
            month: $this->month,
            year: $this->year,
            dateFrom: $this->date_from,
            dateTo: $this->date_to,
            division: $divisionId,
            jobTitle: $this->job_title,
            status: $this->status,
            config: $this->getPeachtreeConfig()
        );

        $filename = 'PEACHTREE_OVERTIME_' . ($this->month ? Carbon::parse($this->month)->format('Y_m') : ($this->year ?: date('Y'))) . ($divisionName ? '_' . Str::slug($divisionName) : '') . '_' . date('Ymd_His') . '.csv';

        $csvContent = $export->toCsvString();

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportExcel()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $divisionId = $this->getEffectiveDivisionId();
        $divisionName = $divisionId ? Division::find($divisionId)?->name : null;

        $filename = 'DATA_LEMBUR_' . ($this->month ? Carbon::parse($this->month)->format('Y_m') : ($this->year ?: date('Y'))) . ($divisionName ? '_' . Str::slug($divisionName) : '') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(
            new OvertimesExport(
                month: $this->month,
                year: $this->year,
                dateFrom: $this->date_from,
                dateTo: $this->date_to,
                division: $divisionId,
                jobTitle: $this->job_title,
                status: $this->status
            ),
            $filename
        );
    }

    public function export()
    {
        if ($this->export_format === 'peachtree_csv') {
            return $this->exportPeachtree();
        }
        return $this->exportExcel();
    }

    public function render()
    {
        $divisionId = $this->getEffectiveDivisionId();

        $peachtreeExport = new PeachtreeOvertimeExport(
            month: $this->month,
            year: $this->year,
            dateFrom: $this->date_from,
            dateTo: $this->date_to,
            division: $divisionId,
            jobTitle: $this->job_title,
            status: $this->status,
            config: $this->getPeachtreeConfig()
        );

        $overtimes = $peachtreeExport->getQuery()->get();

        // Calculate summary stats
        $totalOvertimes = $overtimes->count();
        $totalHours = $overtimes->sum(fn ($o) => (float)($o->duration_hours ?? $o->calculateDuration()));
        $totalPayout = $overtimes->sum(fn ($o) => (float)($o->total_pay ?? $o->overtime_pay ?? 0));
        $peachtreeRowsCount = $totalOvertimes * 9;

        // Generate sample Peachtree preview rows (limit to first 45 distribution rows for speed)
        $peachtreeHeadings = $peachtreeExport->headings();
        $peachtreeRows = $this->preview_tab === 'peachtree' ? $peachtreeExport->array() : [];

        $divisions = Auth::user()->isSuperadmin ? Division::all() : collect();
        $jobTitles = JobTitle::all();

        return view('livewire.admin.import-export.overtime', [
            'overtimes' => $overtimes,
            'peachtreeHeadings' => $peachtreeHeadings,
            'peachtreeRows' => $peachtreeRows,
            'totalOvertimes' => $totalOvertimes,
            'totalHours' => $totalHours,
            'totalPayout' => $totalPayout,
            'peachtreeRowsCount' => $peachtreeRowsCount,
            'divisions' => $divisions,
            'jobTitles' => $jobTitles,
        ]);
    }
}
