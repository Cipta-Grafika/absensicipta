<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendancesExport;
use App\Exports\OvertimesExport;
use App\Exports\PeachtreeOvertimeExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Imports\AttendancesImport;
use App\Imports\UsersImport;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    public function users()
    {
        return view('admin.import-export.users');
    }

    public function attendances()
    {
        return view('admin.import-export.attendances');
    }

    public function workSchedules()
    {
        return view('admin.import-export.work-schedules');
    }

    public function holidays()
    {
        if (!auth()->user()->isSuperadmin) {
            abort(403, 'Akses Ditolak. Hanya SuperAdmin yang berhak mengakses Impor & Ekspor Hari Libur.');
        }
        return view('admin.import-export.holidays');
    }

    public function overtimes()
    {
        if (auth()->user()->isNotAdmin) {
            abort(403, 'Akses Ditolak.');
        }
        return view('admin.import-export.overtimes');
    }

    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx,ods|max:10240',
        ]);

        try {
            Excel::import(new UsersImport(save: true), $request->file('file'));
            return redirect()->route('hr.import-export.users')
                ->with('flash.banner', 'Data karyawan berhasil diimpor.')
                ->with('flash.bannerStyle', 'success');
        } catch (\Throwable $e) {
            return redirect()->route('hr.import-export.users')
                ->with('flash.banner', 'Impor Data Karyawan Gagal: ' . $e->getMessage())
                ->with('flash.bannerStyle', 'danger');
        }
    }

    public function importAttendances(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx,ods|max:10240',
        ]);

        try {
            Excel::import(new AttendancesImport(save: true), $request->file('file'));
            return redirect()->route('hr.import-export.attendances')
                ->with('flash.banner', 'Data absensi berhasil diimpor.')
                ->with('flash.bannerStyle', 'success');
        } catch (\Throwable $e) {
            return redirect()->route('hr.import-export.attendances')
                ->with('flash.banner', 'Impor Data Absensi Gagal: ' . $e->getMessage())
                ->with('flash.bannerStyle', 'danger');
        }
    }

    public function exportUsers()
    {
        return Excel::download(new UsersExport(['user']), 'data_karyawan_export_' . date('Y-m-d') . '.xlsx');
    }

    public function exportAttendances(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $division = $request->input('division');
        $jobTitle = $request->input('job_title');
        $education = $request->input('education');

        if (auth()->user()->group === 'admin' && empty($division)) {
            $division = auth()->user()->division_id;
        }

        return Excel::download(
            new AttendancesExport($month, $year, $division, $jobTitle, $education),
            'data_absensi_export_' . date('Y-m-d') . '.xlsx'
        );
    }

    public function exportPeachtree(Request $request)
    {
        if (auth()->user()->isNotAdmin) {
            abort(403);
        }

        $month = $request->input('month');
        $year = $request->input('year');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $division = $request->input('division');
        $jobTitle = $request->input('job_title');
        $status = $request->input('status', 'approved');

        if (auth()->user()->group === 'admin') {
            $division = auth()->user()->division_id;
        }

        $config = [
            'cash_account_prefix' => $request->input('cash_account_prefix', '10010'),
            'overtime_account_prefix' => $request->input('overtime_account_prefix', '70020'),
            'meal_account_prefix' => $request->input('meal_account_prefix', '70060'),
            'liability_account_prefix' => $request->input('liability_account_prefix', '21120'),
            'expense_account_prefix' => $request->input('expense_account_prefix', '78010'),
            'check_number_mode' => $request->input('check_number_mode', 'custom'),
            'check_number_custom' => $request->input('check_number_custom', ''),
            'division_subaccount_code' => $request->input('division_subaccount_code', ''),
            'transaction_period' => $request->input('transaction_period', ''),
            'pay_method_frequency' => $request->input('pay_method_frequency', '8'),
        ];

        $divisionName = $division ? Division::find($division)?->name : null;
        $filename = 'PEACHTREE_OVERTIME_' . ($month ? Carbon::parse($month)->format('Y_m') : ($year ?: date('Y'))) . ($divisionName ? '_' . Str::slug($divisionName) : '') . '_' . date('Ymd_His') . '.csv';

        $export = new PeachtreeOvertimeExport($month, $year, $dateFrom, $dateTo, $division, $jobTitle, $status, $config);
        $csvContent = $export->toCsvString();

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportOvertimes(Request $request)
    {
        if (auth()->user()->isNotAdmin) {
            abort(403);
        }

        $month = $request->input('month');
        $year = $request->input('year');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $division = $request->input('division');
        $jobTitle = $request->input('job_title');
        $status = $request->input('status', 'approved');

        if (auth()->user()->group === 'admin') {
            $division = auth()->user()->division_id;
        }

        $divisionName = $division ? Division::find($division)?->name : null;
        $filename = 'DATA_LEMBUR_' . ($month ? Carbon::parse($month)->format('Y_m') : ($year ?: date('Y'))) . ($divisionName ? '_' . Str::slug($divisionName) : '') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(
            new OvertimesExport($month, $year, $dateFrom, $dateTo, $division, $jobTitle, $status),
            $filename
        );
    }
}
