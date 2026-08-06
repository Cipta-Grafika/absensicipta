<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

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
}
