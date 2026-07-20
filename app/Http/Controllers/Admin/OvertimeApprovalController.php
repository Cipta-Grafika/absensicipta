<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OvertimeApprovalController extends Controller
{
    public function report(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|date_format:Y-m',
            'week' => 'nullable',
            'division' => 'nullable|exists:divisions,id',
            'jobTitle' => 'nullable|exists:job_titles,id',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $query = Overtime::with(['employee.division', 'employee.jobTitle', 'approver'])
            ->orderBy('created_at', 'desc');

        if (Auth::user()->group === 'admin') {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('division_id', Auth::user()->division_id);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->date) {
            $query->where('overtime_date', $request->date);
        } elseif ($request->week) {
            $start = Carbon::parse($request->week)->startOfWeek()->toDateString();
            $end = Carbon::parse($request->week)->endOfWeek()->toDateString();
            $query->whereBetween('overtime_date', [$start, $end]);
        } elseif ($request->month) {
            $date = Carbon::parse($request->month);
            $query->whereMonth('overtime_date', $date->month)
                  ->whereYear('overtime_date', $date->year);
        }
        
        if ($request->division || $request->jobTitle) {
            $query->whereHas('employee', function (Builder $q) use ($request) {
                if ($request->division) {
                    $q->where('division_id', $request->division);
                }
                if ($request->jobTitle) {
                    $q->where('job_title_id', $request->jobTitle);
                }
            });
        }

        $approvals = $query->get();

        $pdf = Pdf::loadView('admin.overtime-approvals.report', [
            'approvals' => $approvals,
            'date' => $request->date,
            'month' => $request->month,
            'week' => $request->week,
            'division' => $request->division,
            'jobTitle' => $request->jobTitle,
            'status' => $request->status,
        ])->setPaper('a4', 'landscape');
        
        return $pdf->stream('laporan-lembur.pdf');
    }
}
