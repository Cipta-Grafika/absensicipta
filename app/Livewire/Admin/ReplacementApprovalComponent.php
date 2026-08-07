<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\ReplacementHour;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Carbon\Carbon;

class ReplacementApprovalComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $statusFilter = '';
    public ?string $month = null;
    public ?string $week = null;
    public ?string $date = null;
    public ?string $division = null;
    public ?string $jobTitle = null;
    public ?string $search = null;
    
    // Calendar & Bulk Approval State
    public ?string $calendar_month = null;
    public ?string $selected_calendar_date = null;
    public string $selectedDateDisplay = '';
    public bool $bulkReplacementModalOpen = false;
    public array $bulk_replacement_data = []; // Map of [replacement_id => status]
    public ?string $bulk_search = null;

    public $isModalOpen = false;
    public $selectedAttachment = null;
    public $isDeleteModalOpen = false;
    public $replacementToDelete = null;

    protected $updatesQueryString = ['statusFilter'];

    public function mount(): void
    {
        $this->calendar_month = date('Y-m');
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingCalendarMonth()
    {
        $this->resetPage();
    }

    #[On('print-report')]
    public function printReport()
    {
        return redirect()->route('hr.replacement-approvals.report', [
            'month' => $this->month,
            'week' => $this->week,
            'date' => $this->date,
            'division' => $this->division,
            'jobTitle' => $this->jobTitle,
            'status' => $this->statusFilter,
        ]);
    }

    public function handleCalendarDateClick(string $dateString): void
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        $formattedDate = Carbon::parse($dateString)->format('Y-m-d');
        $this->selected_calendar_date = $formattedDate;
        $this->selectedDateDisplay = Carbon::parse($formattedDate)->locale('id')->isoFormat('dddd, DD MMMM YYYY');

        // Fetch existing replacement hours for this date
        $query = ReplacementHour::with(['user.division', 'shift'])
            ->where('replaced_date', $formattedDate);

        if ($user->group === 'admin') {
            $query->whereHas('user', fn ($u) => $u->where('division_id', $user->division_id));
        }

        $replacements = $query->get();

        $this->bulk_replacement_data = [];
        foreach ($replacements as $r) {
            $this->bulk_replacement_data[$r->id] = $r->status;
        }

        $this->resetErrorBag();
        $this->bulk_search = '';
        $this->bulkReplacementModalOpen = true;
    }

    public function bulkSetAllStatus(string $targetStatus): void
    {
        if (!in_array($targetStatus, ['approved', 'rejected', 'pending'], true)) {
            return;
        }

        if (!$this->selected_calendar_date) {
            return;
        }

        $user = Auth::user();
        $query = ReplacementHour::with('user')
            ->where('replaced_date', $this->selected_calendar_date);

        if ($user->group === 'admin') {
            $query->whereHas('user', fn ($u) => $u->where('division_id', $user->division_id));
        }

        if (!empty($this->bulk_search)) {
            $search = strtolower($this->bulk_search);
            $query->whereHas('user', function ($u) use ($search) {
                $u->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('LOWER(nip) LIKE ?', ['%' . $search . '%']);
            });
        }

        $matchingIds = $query->pluck('id')->toArray();
        foreach ($matchingIds as $id) {
            $this->bulk_replacement_data[$id] = $targetStatus;
        }
    }

    public function submitBulkReplacementApproval(): void
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        if (!$this->selected_calendar_date || empty($this->bulk_replacement_data)) {
            $this->bulkReplacementModalOpen = false;
            return;
        }

        DB::transaction(function () use ($user) {
            foreach ($this->bulk_replacement_data as $replacementId => $newStatus) {
                $replacement = ReplacementHour::with(['shift', 'user'])->find($replacementId);
                if (!$replacement) {
                    continue;
                }

                if ($user->group === 'admin' && $replacement->user?->division_id !== $user->division_id) {
                    continue;
                }

                if ($newStatus === 'approved') {
                    $replacement->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id()
                    ]);
                } elseif ($newStatus === 'rejected') {
                    $replacement->update([
                        'status' => 'rejected',
                        'approved_by' => Auth::id()
                    ]);
                } elseif ($newStatus === 'pending') {
                    $replacement->update([
                        'status' => 'pending',
                        'approved_by' => null
                    ]);
                }

                // Recalculate total replacement minutes for attendance
                $totalMinutes = ReplacementHour::where('user_id', $replacement->user_id)
                    ->where('replaced_date', $replacement->replaced_date)
                    ->where('status', 'approved')
                    ->get()
                    ->sum('duration_minutes');

                $attendance = Attendance::where('user_id', $replacement->user_id)
                    ->whereDate('date', $replacement->replaced_date)
                    ->first();

                if ($attendance) {
                    $attendance->replaced_duration_minutes = $totalMinutes;
                    
                    $targetMinutes = $replacement->shift ? $replacement->shift->duration_minutes : 0;
                    $isImpFulfilled = $attendance->status === 'imp' 
                        && $attendance->imp_duration_minutes > 0 
                        && $totalMinutes >= $attendance->imp_duration_minutes;
                    $isShiftFulfilled = $targetMinutes > 0 && $totalMinutes >= $targetMinutes;
                    
                    if ($isImpFulfilled || $isShiftFulfilled) {
                        $attendance->status = 'present';
                    }
                    
                    $attendance->save();
                    Attendance::clearUserAttendanceCache($attendance->user, Carbon::parse($attendance->date));
                }
            }
        });

        $this->bulkReplacementModalOpen = false;
        $this->banner('Data approval ganti jam untuk ' . $this->selectedDateDisplay . ' berhasil disimpan.');
    }

    public function render()
    {
        $user = Auth::user();
        $selectedMonth = $this->calendar_month ?: date('Y-m');

        $calStart = Carbon::parse($selectedMonth)->startOfMonth();
        $calEnd = Carbon::parse($selectedMonth)->endOfMonth();
        $calDates = $calStart->range($calEnd)->toArray();

        // Query monthly replacements for calendar grid visualization
        $monthReplacementsQuery = ReplacementHour::with(['user.division', 'shift'])
            ->whereBetween('replaced_date', [$calStart->format('Y-m-d'), $calEnd->format('Y-m-d')]);

        if ($user->group === 'admin') {
            $monthReplacementsQuery->whereHas('user', fn ($u) => $u->where('division_id', $user->division_id));
        }

        $monthReplacements = $monthReplacementsQuery->get()->groupBy(fn($r) => Carbon::parse($r->replaced_date)->format('Y-m-d'));

        // Query for table listing
        $query = ReplacementHour::with(['user', 'approver', 'shift'])
            ->orderBy('created_at', 'desc');

        if ($user->group === 'admin') {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('division_id', $user->division_id);
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        if ($this->date) {
            $query->where('replaced_date', $this->date);
        } elseif ($this->week) {
            $start = Carbon::parse($this->week)->startOfWeek()->toDateString();
            $end = Carbon::parse($this->week)->endOfWeek()->toDateString();
            $query->whereBetween('replaced_date', [$start, $end]);
        } elseif ($this->month) {
            $date = Carbon::parse($this->month);
            $query->whereMonth('replaced_date', $date->month)
                  ->whereYear('replaced_date', $date->year);
        } else {
            // Default filter to selected calendar month if no explicit date/week/month filter applied
            $query->whereBetween('replaced_date', [$calStart->format('Y-m-d'), $calEnd->format('Y-m-d')]);
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->division || $this->jobTitle) {
            $query->whereHas('user', function ($q) use ($user) {
                if ($this->division) {
                    if ($user->group === 'admin' && $this->division != $user->division_id) {
                        $q->whereRaw('1 = 0');
                    } else {
                        $q->where('division_id', $this->division);
                    }
                }
                if ($this->jobTitle) {
                    $q->where('job_title_id', $this->jobTitle);
                }
            });
        }

        $approvals = $query->paginate(15);

        // Fetch replacement items for modal if bulk modal is open
        $modalReplacementItems = collect();
        if ($this->bulkReplacementModalOpen && $this->selected_calendar_date) {
            $mQuery = ReplacementHour::with(['user.division', 'shift'])
                ->where('replaced_date', $this->selected_calendar_date);

            if ($user->group === 'admin') {
                $mQuery->whereHas('user', fn ($u) => $u->where('division_id', $user->division_id));
            }

            if (!empty($this->bulk_search)) {
                $search = strtolower($this->bulk_search);
                $mQuery->whereHas('user', function ($u) use ($search) {
                    $u->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                      ->orWhereRaw('LOWER(nip) LIKE ?', ['%' . $search . '%']);
                });
            }

            $modalReplacementItems = $mQuery->get();
        }

        return view('livewire.admin.replacement-approval-component', [
            'approvals' => $approvals,
            'calDates' => $calDates,
            'calStart' => $calStart,
            'calEnd' => $calEnd,
            'monthReplacements' => $monthReplacements,
            'modalReplacementItems' => $modalReplacementItems,
        ])->layout('layouts.app');
    }

    public function approve($id)
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        $replacement = ReplacementHour::with(['shift', 'user'])->findOrFail($id);

        if ($user->group === 'admin' && $replacement->user?->division_id !== $user->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menyetujui ganti jam divisi Anda.');
        }

        $replacement->update([
            'status' => 'approved',
            'approved_by' => Auth::id()
        ]);
        
        // Cek total akumulasi menit ganti jam pada tanggal tersebut
        $totalMinutes = ReplacementHour::where('user_id', $replacement->user_id)
            ->where('replaced_date', $replacement->replaced_date)
            ->where('status', 'approved')
            ->get()
            ->sum('duration_minutes');

        // Update Attendance record
        $attendance = Attendance::where('user_id', $replacement->user_id)
            ->whereDate('date', $replacement->replaced_date)
            ->first();

        if ($attendance) {
            $attendance->replaced_duration_minutes = $totalMinutes;
            
            $targetMinutes = $replacement->shift ? $replacement->shift->duration_minutes : 0;
            
            $isImpFulfilled = $attendance->status === 'imp' 
                && $attendance->imp_duration_minutes > 0 
                && $totalMinutes >= $attendance->imp_duration_minutes;
                
            $isShiftFulfilled = $targetMinutes > 0 && $totalMinutes >= $targetMinutes;
            
            if ($isImpFulfilled || $isShiftFulfilled) {
                $attendance->status = 'present';
            }
            
            $attendance->save();
            Attendance::clearUserAttendanceCache($attendance->user, Carbon::parse($attendance->date));
        }

        $this->banner('Pengajuan berhasil disetujui.');
    }

    public function reject($id)
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        $replacement = ReplacementHour::with('user')->findOrFail($id);

        if ($user->group === 'admin' && $replacement->user?->division_id !== $user->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menolak ganti jam divisi Anda.');
        }

        $replacement->update([
            'status' => 'rejected',
            'approved_by' => Auth::id()
        ]);

        // Recalculate attendance
        $totalMinutes = ReplacementHour::where('user_id', $replacement->user_id)
            ->where('replaced_date', $replacement->replaced_date)
            ->where('status', 'approved')
            ->get()
            ->sum('duration_minutes');

        $attendance = Attendance::where('user_id', $replacement->user_id)
            ->whereDate('date', $replacement->replaced_date)
            ->first();

        if ($attendance) {
            $attendance->replaced_duration_minutes = $totalMinutes;
            $attendance->save();
            Attendance::clearUserAttendanceCache($attendance->user, Carbon::parse($attendance->date));
        }

        $this->banner('Pengajuan telah ditolak.');
    }

    public function confirmDelete($id)
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        $replacement = ReplacementHour::with('user')->findOrFail($id);

        if ($user->group === 'admin' && $replacement->user?->division_id !== $user->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menghapus data ganti jam divisi Anda.');
        }

        $this->replacementToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function cancelDelete()
    {
        $this->isDeleteModalOpen = false;
        $this->replacementToDelete = null;
    }

    public function deleteReplacement()
    {
        $user = Auth::user();
        if ($user->isNotAdmin || !$this->replacementToDelete) {
            abort(403);
        }
        
        $replacement = ReplacementHour::with('user')->findOrFail($this->replacementToDelete);

        if ($user->group === 'admin' && $replacement->user?->division_id !== $user->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menghapus data ganti jam divisi Anda.');
        }
        
        if ($replacement->attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($replacement->attachment);
        }
        
        $replacement->delete();
        
        $this->cancelDelete();
        $this->banner('Data pengajuan ganti jam berhasil dihapus.');
    }

    public function viewAttachment($attachmentPath)
    {
        $this->selectedAttachment = $attachmentPath;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedAttachment = null;
    }
}
