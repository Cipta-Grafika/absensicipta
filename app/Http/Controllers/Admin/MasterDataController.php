<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class MasterDataController extends Controller
{
    public function division()
    {
        return view('admin.master-data.division');
    }

    public function jobTitle()
    {
        return view('admin.master-data.job-title');
    }

    public function education()
    {
        return view('admin.master-data.education');
    }

    public function shift()
    {
        return view('admin.master-data.shift');
    }

    public function admin()
    {
        return view('admin.master-data.admin');
    }

    public function overtimeRate()
    {
        return view('admin.master-data.overtime-rate');
    }

    public function leaderboard()
    {
        return view('admin.master-data.leaderboard');
    }

    public function scanFeedback()
    {
        return view('admin.master-data.scan-feedback');
    }
}
