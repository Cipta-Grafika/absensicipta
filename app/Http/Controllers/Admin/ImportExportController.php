<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendancesExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Imports\AttendancesImport;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
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
}
