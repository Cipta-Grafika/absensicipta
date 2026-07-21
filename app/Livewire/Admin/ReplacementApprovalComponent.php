<?php

namespace App\Livewire\Admin;

use App\Models\ReplacementHour;
use Illuminate\Support\Facades\Auth;
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
    
    public $isModalOpen = false;
    public $selectedAttachment = null;
    public $isDeleteModalOpen = false;
    public $replacementToDelete = null;

    protected $updatesQueryString = ['statusFilter'];

    public function updatingStatusFilter()
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

    public function render()
    {
        $query = ReplacementHour::with(['user', 'approver', 'shift'])
            ->orderBy('created_at', 'desc');

        if (Auth::user()->group === 'admin') {
            $query->whereHas('user', function ($q) {
                $q->where('division_id', Auth::user()->division_id);
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
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->division || $this->jobTitle) {
            $query->whereHas('user', function ($q) {
                if ($this->division) {
                    $q->where('division_id', $this->division);
                }
                if ($this->jobTitle) {
                    $q->where('job_title_id', $this->jobTitle);
                }
            });
        }

        $approvals = $query->paginate(15);

        return view('livewire.admin.replacement-approval-component', [
            'approvals' => $approvals
        ])->layout('layouts.app');
    }

    public function approve($id)
    {
        if (!in_array(Auth::user()->group, ['admin', 'superadmin'])) {
            abort(403);
        }

        $replacement = ReplacementHour::with(['shift', 'user'])->findOrFail($id);

        if (Auth::user()->group === 'admin' && $replacement->user->division_id !== Auth::user()->division_id) {
            abort(403, 'Unauthorized access.');
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
        $attendance = \App\Models\Attendance::where('user_id', $replacement->user_id)
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
            \App\Models\Attendance::clearUserAttendanceCache($attendance->user, \Illuminate\Support\Carbon::parse($attendance->date));
        }

        $this->banner('Pengajuan berhasil disetujui.');
    }

    public function reject($id)
    {
        if (!in_array(Auth::user()->group, ['admin', 'superadmin'])) {
            abort(403);
        }

        $replacement = ReplacementHour::with('user')->findOrFail($id);

        if (Auth::user()->group === 'admin' && $replacement->user->division_id !== Auth::user()->division_id) {
            abort(403, 'Unauthorized access.');
        }

        $replacement->update([
            'status' => 'rejected',
            'approved_by' => Auth::id()
        ]);

        $this->banner('Pengajuan telah ditolak.');
    }

    public function confirmDelete($id)
    {
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
        if (!Auth::user()->isSuperadmin || !$this->replacementToDelete) {
            abort(403);
        }
        
        $replacement = ReplacementHour::findOrFail($this->replacementToDelete);
        
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
