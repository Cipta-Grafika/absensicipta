<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReplacementHour;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReplacementApprovalController extends Controller
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

        if (!$request->date && !$request->month && !$request->week && !$request->division && !$request->jobTitle && !$request->status) {
            // Jika tidak ada filter apapun, kita kembalikan saja (atau set default behavior)
            // Tapi untuk cetak semua, kita perbolehkan saja.
        }

        $query = ReplacementHour::with(['user.division', 'user.jobTitle', 'approver', 'shift'])
            ->orderBy('created_at', 'desc');

        if (Auth::user()->group === 'admin') {
            $query->whereHas('user', function (Builder $q) {
                $q->where('division_id', Auth::user()->division_id);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->date) {
            $query->where('replaced_date', $request->date);
        } elseif ($request->week) {
            $start = Carbon::parse($request->week)->startOfWeek()->toDateString();
            $end = Carbon::parse($request->week)->endOfWeek()->toDateString();
            $query->whereBetween('replaced_date', [$start, $end]);
        } elseif ($request->month) {
            $date = Carbon::parse($request->month);
            $query->whereMonth('replaced_date', $date->month)
                  ->whereYear('replaced_date', $date->year);
        }
        
        if ($request->division || $request->jobTitle) {
            $query->whereHas('user', function (Builder $q) use ($request) {
                if ($request->division) {
                    $q->where('division_id', $request->division);
                }
                if ($request->jobTitle) {
                    $q->where('job_title_id', $request->jobTitle);
                }
            });
        }

        $approvals = $query->get();

        $pdf = Pdf::loadView('admin.replacement-approvals.report', [
            'approvals' => $approvals,
            'date' => $request->date,
            'month' => $request->month,
            'week' => $request->week,
            'division' => $request->division,
            'jobTitle' => $request->jobTitle,
            'status' => $request->status,
        ])->setPaper('a4', 'landscape');
        
        return $pdf->stream('laporan-ganti-jam.pdf');
    }
}
