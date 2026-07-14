<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UserAttendanceController extends Controller
{
    public function applyLeave()
    {
        $attendance = null;
        $shifts = \App\Models\Shift::all();
        return view('attendances.apply-leave', ['attendance' => $attendance, 'shifts' => $shifts]);
    }

    public function storeLeaveRequest(Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:excused,sick,leave,wfh,imp,special-leaves'],
            'note' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'imp_duration_minutes' => ['nullable', 'string', 'regex:/^([0-9]+):([0-5][0-9])$/', 'required_if:status,imp'],
            'shift_id' => ['nullable', 'exists:shifts,id', 'required_if:status,imp'],
            'attachment' => ['nullable', 'file', 'max:3072'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);
        try {
            // Save new attachment file
            $newAttachment = null;
            if ($request->file('attachment')) {
                $newAttachment = $request->file('attachment')->storePublicly(
                    'attachments',
                    ['disk' => config('jetstream.attachment_disk')]
                );
            }

            $fromDate = Carbon::parse($request->from);
            $toDate = Carbon::parse($request->to ?? $fromDate);

            $parsedImpDurationMinutes = null;
            if ($request->status === 'imp' && $request->imp_duration_minutes) {
                list($h, $m) = explode(':', $request->imp_duration_minutes);
                $parsedImpDurationMinutes = ((int)$h * 60) + (int)$m;
                if ($parsedImpDurationMinutes <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'imp_duration_minutes' => 'Durasi IMP tidak boleh 0.'
                    ]);
                }
            }

            $hasPresentOrLate = Attendance::where('user_id', Auth::user()->id)
                ->whereBetween('date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
                ->whereIn('status', ['present', 'late'])
                ->first();

            if ($hasPresentOrLate) {
                if ($hasPresentOrLate->status === 'late') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'from' => 'Anda tidak dapat mengajukan izin/status lain pada tanggal ' . Carbon::parse($hasPresentOrLate->date)->format('d/m/Y') . ' karena Anda sudah tercatat Terlambat pada hari tersebut.'
                    ]);
                } else if ($hasPresentOrLate->status === 'present' && $request->status !== 'imp') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'from' => 'Anda tidak dapat mengajukan izin/status lain pada tanggal ' . Carbon::parse($hasPresentOrLate->date)->format('d/m/Y') . ' karena Anda sudah tercatat Hadir pada hari tersebut. Anda hanya dapat mengajukan Izin Meninggalkan Pekerjaan (IMP).'
                    ]);
                }
            }

            $fromDate->range($toDate)
                ->forEach(function (Carbon $date) use ($request, $newAttachment, $parsedImpDurationMinutes) {
                    $existing = Attendance::where('user_id', Auth::user()->id)
                        ->where('date', $date->format('Y-m-d'))
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'status' => $request->status,
                            'note' => $request->note,
                            'attachment' => $newAttachment ?? $existing->attachment,
                            'latitude' => doubleval($request->lat) ?? $existing->latitude,
                            'longitude' => doubleval($request->lng) ?? $existing->longitude,
                            'imp_duration_minutes' => $request->status === 'imp' ? $parsedImpDurationMinutes : $existing->imp_duration_minutes,
                            'shift_id' => $request->status === 'imp' ? $request->shift_id : $existing->shift_id,
                        ]);
                    } else {
                        Attendance::create([
                            'user_id' => Auth::user()->id,
                            'status' => $request->status,
                            'date' => $date->format('Y-m-d'),
                            'note' => $request->note,
                            'attachment' => $newAttachment ?? null,
                            'latitude' => $request->lat ? doubleval($request->lat) : null,
                            'longitude' => $request->lng ? doubleval($request->lng) : null,
                            'imp_duration_minutes' => $request->status === 'imp' ? $parsedImpDurationMinutes : null,
                            'shift_id' => $request->status === 'imp' ? $request->shift_id : null,
                        ]);
                    }
                });

            Attendance::clearUserAttendanceCache(Auth::user(), $fromDate);
            if (!$fromDate->isSameMonth($toDate)) {
                Attendance::clearUserAttendanceCache(Auth::user(), $toDate);
            }

            return redirect(route('home'))
                ->with('flash.banner', __('Created successfully.'));
        } catch (\Throwable $th) {
            return redirect()->back()
                ->with('flash.banner', $th->getMessage())
                ->with('flash.bannerStyle', 'danger');
        }
    }

    public function history()
    {
        return view('attendances.history');
    }
}
