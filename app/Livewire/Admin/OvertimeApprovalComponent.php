<?php

namespace App\Livewire\Admin;

use App\Models\Overtime;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Carbon\Carbon;

class OvertimeApprovalComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $statusFilter = '';
    public ?string $month = null;
    public ?string $week = null;
    public ?string $date = null;
    public ?string $division = null;
    public ?string $jobTitle = null;
    public ?string $search = null;
    
    public $isDeleteModalOpen = false;
    public $overtimeToDelete = null;

    protected $updatesQueryString = ['statusFilter'];

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    #[On('print-report')]
    public function printReport()
    {
        return redirect()->route('hr.overtime-approvals.report', [
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
        $query = Overtime::with(['employee', 'approver'])
            ->orderBy('created_at', 'desc');

        if (Auth::user()->group === 'admin') {
            $query->whereHas('employee', function ($q) {
                $q->where('division_id', Auth::user()->division_id);
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        if ($this->date) {
            $query->where('overtime_date', $this->date);
        } elseif ($this->week) {
            $start = Carbon::parse($this->week)->startOfWeek()->toDateString();
            $end = Carbon::parse($this->week)->endOfWeek()->toDateString();
            $query->whereBetween('overtime_date', [$start, $end]);
        } elseif ($this->month) {
            $date = Carbon::parse($this->month);
            $query->whereMonth('overtime_date', $date->month)
                  ->whereYear('overtime_date', $date->year);
        }

        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->division || $this->jobTitle) {
            $query->whereHas('employee', function ($q) {
                if ($this->division) {
                    $q->where('division_id', $this->division);
                }
                if ($this->jobTitle) {
                    $q->where('job_title_id', $this->jobTitle);
                }
            });
        }

        $approvals = $query->paginate(15);

        return view('livewire.admin.overtime-approval-component', [
            'approvals' => $approvals
        ])->layout('layouts.app');
    }

    public function approve($id)
    {
        if (!in_array(Auth::user()->group, ['admin', 'superadmin'])) {
            abort(403);
        }

        $overtime = Overtime::with('employee')->findOrFail($id);

        if (Auth::user()->group === 'admin' && $overtime->employee->division_id !== Auth::user()->division_id) {
            abort(403, 'Unauthorized access.');
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approval_date' => now(),
        ]);

        $this->banner('Pengajuan lembur berhasil disetujui.');
    }

    public function reject($id)
    {
        if (!in_array(Auth::user()->group, ['admin', 'superadmin'])) {
            abort(403);
        }

        $overtime = Overtime::with('employee')->findOrFail($id);

        if (Auth::user()->group === 'admin' && $overtime->employee->division_id !== Auth::user()->division_id) {
            abort(403, 'Unauthorized access.');
        }

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approval_date' => now(),
        ]);

        $this->banner('Pengajuan lembur telah ditolak.');
    }

    public function confirmDelete($id)
    {
        $this->overtimeToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function cancelDelete()
    {
        $this->isDeleteModalOpen = false;
        $this->overtimeToDelete = null;
    }

    public function deleteOvertime()
    {
        if (!Auth::user()->isSuperadmin || !$this->overtimeToDelete) {
            abort(403);
        }
        
        $overtime = Overtime::findOrFail($this->overtimeToDelete);
        
        $overtime->delete();
        
        $this->cancelDelete();
        $this->banner('Data pengajuan lembur berhasil dihapus.');
    }
}
