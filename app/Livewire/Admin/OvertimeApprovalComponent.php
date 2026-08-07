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
    
    // Calendar & Bulk Approval State
    public ?string $calendar_month = null;
    public ?string $selected_calendar_date = null;
    public string $selectedDateDisplay = '';
    public bool $bulkOvertimeModalOpen = false;
    public array $bulk_overtime_data = []; // Map of [overtime_id => status]
    public ?string $bulk_search = null;

    public $isDeleteModalOpen = false;
    public $overtimeToDelete = null;

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
        return redirect()->route('hr.overtime-approvals.report', [
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

        // Fetch existing overtimes for this date
        $query = Overtime::with(['employee.division'])
            ->where('overtime_date', $formattedDate);

        if ($user->group === 'admin') {
            $query->whereHas('employee', fn ($u) => $u->where('division_id', $user->division_id));
        }

        $overtimes = $query->get();

        $this->bulk_overtime_data = [];
        foreach ($overtimes as $o) {
            $this->bulk_overtime_data[$o->id] = $o->status;
        }

        $this->resetErrorBag();
        $this->bulk_search = '';
        $this->bulkOvertimeModalOpen = true;
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
        $query = Overtime::with('employee')
            ->where('overtime_date', $this->selected_calendar_date);

        if ($user->group === 'admin') {
            $query->whereHas('employee', fn ($u) => $u->where('division_id', $user->division_id));
        }

        if (!empty($this->bulk_search)) {
            $search = strtolower($this->bulk_search);
            $query->whereHas('employee', function ($u) use ($search) {
                $u->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('LOWER(nip) LIKE ?', ['%' . $search . '%']);
            });
        }

        $matchingIds = $query->pluck('id')->toArray();
        foreach ($matchingIds as $id) {
            $this->bulk_overtime_data[$id] = $targetStatus;
        }
    }

    public function submitBulkOvertimeApproval(): void
    {
        $user = Auth::user();
        if ($user->isNotAdmin) {
            abort(403);
        }

        if (!$this->selected_calendar_date || empty($this->bulk_overtime_data)) {
            $this->bulkOvertimeModalOpen = false;
            return;
        }

        $formattedDate = $this->selected_calendar_date;

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $formattedDate) {
            foreach ($this->bulk_overtime_data as $overtimeId => $newStatus) {
                $overtime = Overtime::with('employee')->find($overtimeId);
                if (!$overtime) {
                    continue;
                }

                if ($user->group === 'admin' && $overtime->employee?->division_id !== $user->division_id) {
                    continue;
                }

                if ($newStatus === 'approved') {
                    $payData = \App\Models\OvertimeRate::calculatePayForDuration((float) $overtime->duration_hours, $overtime->employee);
                    $overtime->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approval_date' => now(),
                        'applied_rate_amount' => $payData['applied_rate_amount'],
                        'total_pay' => $payData['total_pay'],
                    ]);
                } elseif ($newStatus === 'rejected') {
                    $overtime->update([
                        'status' => 'rejected',
                        'approved_by' => Auth::id(),
                        'approval_date' => now(),
                    ]);
                } elseif ($newStatus === 'pending') {
                    $overtime->update([
                        'status' => 'pending',
                        'approved_by' => null,
                        'approval_date' => null,
                        'applied_rate_amount' => null,
                        'total_pay' => null,
                    ]);
                }
            }
        });

        $this->bulkOvertimeModalOpen = false;
        $this->banner('Data approval lembur untuk ' . $this->selectedDateDisplay . ' berhasil disimpan.');
    }

    public function render()
    {
        $user = Auth::user();
        $selectedMonth = $this->calendar_month ?: date('Y-m');

        $calStart = Carbon::parse($selectedMonth)->startOfMonth();
        $calEnd = Carbon::parse($selectedMonth)->endOfMonth();
        $calDates = $calStart->range($calEnd)->toArray();

        // Query monthly overtimes for calendar grid visualization
        $monthOvertimesQuery = Overtime::with(['employee.division'])
            ->whereBetween('overtime_date', [$calStart->format('Y-m-d'), $calEnd->format('Y-m-d')]);

        if ($user->group === 'admin') {
            $monthOvertimesQuery->whereHas('employee', fn ($u) => $u->where('division_id', $user->division_id));
        }

        $monthOvertimes = $monthOvertimesQuery->get()->groupBy(fn($o) => Carbon::parse($o->overtime_date)->format('Y-m-d'));

        // Query for date modal items if modal is open
        $modalOvertimeItems = collect();
        if ($this->bulkOvertimeModalOpen && $this->selected_calendar_date) {
            $modalQuery = Overtime::with(['employee.division'])
                ->where('overtime_date', $this->selected_calendar_date);

            if ($user->group === 'admin') {
                $modalQuery->whereHas('employee', fn ($u) => $u->where('division_id', $user->division_id));
            }

            if (!empty($this->bulk_search)) {
                $search = strtolower($this->bulk_search);
                $modalQuery->whereHas('employee', function ($u) use ($search) {
                    $u->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                      ->orWhereRaw('LOWER(nip) LIKE ?', ['%' . $search . '%']);
                });
            }

            $modalOvertimeItems = $modalQuery->get();
        }

        $query = Overtime::with(['employee', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($user->group === 'admin') {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('division_id', $user->division_id);
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
            $query->whereHas('employee', function ($q) use ($user) {
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

        return view('livewire.admin.overtime-approval-component', [
            'approvals' => $approvals,
            'monthOvertimes' => $monthOvertimes,
            'modalOvertimeItems' => $modalOvertimeItems,
            'calDates' => $calDates,
            'calStart' => $calStart,
            'calEnd' => $calEnd,
            'calendar_month' => $selectedMonth,
        ])->layout('layouts.app');
    }

    public function approve($id)
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $overtime = Overtime::with('employee')->findOrFail($id);

        if (Auth::user()->group === 'admin' && $overtime->employee?->division_id !== Auth::user()->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menyetujui lembur divisi Anda.');
        }

        $payData = \App\Models\OvertimeRate::calculatePayForDuration((float) $overtime->duration_hours, $overtime->employee);

        $overtime->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approval_date' => now(),
            'applied_rate_amount' => $payData['applied_rate_amount'],
            'total_pay' => $payData['total_pay'],
        ]);

        $this->banner('Pengajuan lembur berhasil disetujui.');
    }

    public function reject($id)
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $overtime = Overtime::with('employee')->findOrFail($id);

        if (Auth::user()->group === 'admin' && $overtime->employee?->division_id !== Auth::user()->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menolak lembur divisi Anda.');
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
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        $overtime = Overtime::with('employee')->findOrFail($id);

        if (Auth::user()->group === 'admin' && $overtime->employee?->division_id !== Auth::user()->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menghapus data lembur divisi Anda.');
        }

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
        if (Auth::user()->isNotAdmin || !$this->overtimeToDelete) {
            abort(403);
        }
        
        $overtime = Overtime::with('employee')->findOrFail($this->overtimeToDelete);

        if (Auth::user()->group === 'admin' && $overtime->employee?->division_id !== Auth::user()->division_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menghapus data lembur divisi Anda.');
        }
        
        $overtime->delete();
        
        $this->cancelDelete();
        $this->banner('Data pengajuan lembur berhasil dihapus.');
    }
}
