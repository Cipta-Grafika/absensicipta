<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\Division;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LeaveManagementComponent extends Component
{
    use WithPagination, WithFileUploads, InteractsWithBanner;

    // Filters
    public int $year;
    public ?string $division_id = '';
    public ?string $quota_status = 'all'; // all, safe, critical, empty, minus
    public ?string $search = '';
    public int $perPage = 15;

    // Modals
    public bool $isHistoryModalOpen = false;
    public ?string $selectedUserId = null;
    public ?User $selectedUser = null;
    public ?EmployeeLeaveBalance $selectedUserBalance = null;
    public $userLeaveHistory = [];

    // Adjust Quota Modal
    public bool $isAdjustModalOpen = false;
    public ?string $adjustUserId = null;
    public ?string $adjustUserName = '';
    public int $formInitialQuota = 12;
    public int $formCarryForward = 0;
    public int $formAdjustment = 0;
    public ?string $formExpiredAt = null;
    public ?string $formNote = '';

    // Add Leave by Superadmin Modal
    public bool $isAddLeaveModalOpen = false;
    public ?string $addLeaveUserId = '';
    public string $addLeaveStatus = 'leave';
    public ?string $addLeaveFrom = '';
    public ?string $addLeaveTo = '';
    public ?string $addLeaveNote = '';
    public $addLeaveAttachment = null;

    // Master Leave Types Modal
    public bool $isLeaveTypesModalOpen = false;
    public bool $isEditingLeaveType = false;
    public ?int $editingLeaveTypeId = null;
    public string $typeCode = '';
    public string $typeName = '';
    public int $typeDefaultDays = 1;
    public bool $typeDeductsQuota = false;
    public bool $typeRequiresAttachment = false;
    public bool $typeIsActive = true;
    public ?string $typeDescription = '';

    public function mount(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin, 403, 'Akses khusus SUPERADMIN GROUP.');
        $this->year = (int) date('Y');
        $this->addLeaveFrom = date('Y-m-d');
        $this->addLeaveTo = date('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDivisionId(): void
    {
        $this->resetPage();
    }

    public function updatingQuotaStatus(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Bulk synchronize leave balances for all active employees for selected year.
     */
    #[On('bulk-sync-all')]
    public function bulkSyncAll(): void
    {
        EmployeeLeaveBalance::syncAllForYear($this->year);
        $this->banner("Sinkronisasi kuota dan riwayat cuti tahun {$this->year} untuk seluruh karyawan berhasil dilakukan.");
    }

    /**
     * Open History Breakdown Modal for a specific employee.
     */
    public function openHistoryModal(string $userId): void
    {
        $this->selectedUserId = $userId;
        $this->selectedUser = User::with('division', 'jobTitle')->findOrFail($userId);
        $this->selectedUserBalance = EmployeeLeaveBalance::getOrCreateForUser($this->selectedUser, $this->year);
        $this->selectedUserBalance->syncUsedQuota();
        $this->selectedUserBalance->refresh();

        $this->loadUserLeaveHistory();
        $this->isHistoryModalOpen = true;
    }

    public function loadUserLeaveHistory(): void
    {
        if (!$this->selectedUserId) {
            $this->userLeaveHistory = [];
            return;
        }

        $this->userLeaveHistory = Attendance::where('user_id', $this->selectedUserId)
            ->whereYear('date', $this->year)
            ->whereIn('status', ['leave', 'special-leaves'])
            ->orderBy('date', 'desc')
            ->get();
    }

    public function closeHistoryModal(): void
    {
        $this->isHistoryModalOpen = false;
        $this->selectedUserId = null;
        $this->selectedUser = null;
        $this->selectedUserBalance = null;
        $this->userLeaveHistory = [];
    }

    /**
     * Delete an attendance leave record (restoring the quota automatically).
     */
    public function deleteLeaveRecord(int $attendanceId): void
    {
        $att = Attendance::find($attendanceId);
        if ($att) {
            $date = Carbon::parse($att->date);
            $userId = $att->user_id;
            $att->delete();

            Attendance::clearUserAttendanceCache(User::find($userId), $date);

            if ($this->selectedUserBalance) {
                $this->selectedUserBalance->syncUsedQuota();
                $this->selectedUserBalance->refresh();
            }

            $this->loadUserLeaveHistory();
            $this->banner('Catatan cuti pada tanggal ' . $date->format('d/m/Y') . ' berhasil dibatalkan/dihapus dan kuota cuti otomatis dikembalikan.');
        }
    }

    /**
     * Open Adjust Quota Modal.
     */
    public function openAdjustModal(string $userId): void
    {
        $user = User::findOrFail($userId);
        $balance = EmployeeLeaveBalance::getOrCreateForUser($user, $this->year);
        $balance->syncUsedQuota();
        $balance->refresh();

        $this->adjustUserId = $userId;
        $this->adjustUserName = $user->name;
        $this->formInitialQuota = (int) $balance->initial_quota;
        $this->formCarryForward = (int) $balance->carry_forward;
        $this->formAdjustment = (int) $balance->adjustment;
        $this->formExpiredAt = $balance->expired_at ? Carbon::parse($balance->expired_at)->format('Y-m-d') : null;
        $this->formNote = $balance->note ?? '';

        $this->isAdjustModalOpen = true;
    }

    public function closeAdjustModal(): void
    {
        $this->isAdjustModalOpen = false;
        $this->adjustUserId = null;
        $this->adjustUserName = '';
    }

    /**
     * Save Quota Adjustment.
     */
    public function saveAdjustment(): void
    {
        $this->validate([
            'formInitialQuota' => 'required|integer|min:0|max:100',
            'formCarryForward' => 'required|integer|min:0|max:100',
            'formAdjustment' => 'required|integer|min:-100|max:100',
            'formExpiredAt' => 'nullable|date',
            'formNote' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($this->adjustUserId);
        $balance = EmployeeLeaveBalance::getOrCreateForUser($user, $this->year);

        $balance->update([
            'initial_quota' => $this->formInitialQuota,
            'carry_forward' => $this->formCarryForward,
            'adjustment' => $this->formAdjustment,
            'expired_at' => $this->formExpiredAt ?: null,
            'note' => $this->formNote,
        ]);

        $balance->syncUsedQuota();
        $this->closeAdjustModal();

        if ($this->isHistoryModalOpen && $this->selectedUserId === $user->id) {
            $this->selectedUserBalance = $balance->fresh();
        }

        $this->banner("Penyesuaian kuota cuti untuk {$user->name} pada tahun {$this->year} berhasil disimpan.");
    }

    /**
     * Open Modal to Add/Assign Leave by Superadmin.
     */
    #[On('open-add-leave-modal')]
    #[On('show-creating')]
    public function openAddLeaveModal(?string $userId = null): void
    {
        $this->resetValidation();
        $this->addLeaveUserId = $userId ?: '';
        $this->addLeaveStatus = 'leave';
        $this->addLeaveFrom = date('Y-m-d');
        $this->addLeaveTo = date('Y-m-d');
        $this->addLeaveNote = '';
        $this->addLeaveAttachment = null;
        $this->isAddLeaveModalOpen = true;
    }

    public function closeAddLeaveModal(): void
    {
        $this->isAddLeaveModalOpen = false;
        $this->addLeaveAttachment = null;
    }

    /**
     * Save Leave Submission created by Superadmin.
     */
    public function saveAddLeave(): void
    {
        $this->validate([
            'addLeaveUserId' => 'required|exists:users,id',
            'addLeaveStatus' => 'required|in:leave,special-leaves',
            'addLeaveFrom' => 'required|date',
            'addLeaveTo' => 'required|date|after_or_equal:addLeaveFrom',
            'addLeaveNote' => 'required|string|max:255',
            'addLeaveAttachment' => 'nullable|file|max:3072',
        ]);

        try {
            $user = User::findOrFail($this->addLeaveUserId);
            $fromDate = Carbon::parse($this->addLeaveFrom);
            $toDate = Carbon::parse($this->addLeaveTo);

            $attachmentPath = null;
            if ($this->addLeaveAttachment) {
                $attachmentPath = $this->addLeaveAttachment->storePublicly(
                    'attachments',
                    ['disk' => config('jetstream.attachment_disk')]
                );
            }

            // Iterate and create/update attendance
            $fromDate->range($toDate)->forEach(function (Carbon $date) use ($user, $attachmentPath) {
                $existing = Attendance::where('user_id', $user->id)
                    ->where('date', $date->format('Y-m-d'))
                    ->get();

                if ($existing->isNotEmpty()) {
                    $first = $existing->first();
                    $existing->where('id', '!=', $first->id)->each->delete();

                    $first->update([
                        'status' => $this->addLeaveStatus,
                        'note' => $this->addLeaveNote,
                        'attachment' => $attachmentPath ?? $first->attachment,
                    ]);
                } else {
                    Attendance::create([
                        'user_id' => $user->id,
                        'status' => $this->addLeaveStatus,
                        'date' => $date->format('Y-m-d'),
                        'note' => $this->addLeaveNote,
                        'attachment' => $attachmentPath,
                    ]);
                }
            });

            Attendance::clearUserAttendanceCache($user, $fromDate);
            if (!$fromDate->isSameMonth($toDate)) {
                Attendance::clearUserAttendanceCache($user, $toDate);
            }

            // Sync balance
            EmployeeLeaveBalance::syncForUserAndYear($user->id, $fromDate->year);

            $this->closeAddLeaveModal();

            if ($this->isHistoryModalOpen && $this->selectedUserId === $user->id) {
                $this->loadUserLeaveHistory();
                $this->selectedUserBalance = EmployeeLeaveBalance::getOrCreateForUser($user, $this->year);
                $this->selectedUserBalance->refresh();
            }

            $this->banner("Cuti untuk {$user->name} berhasil dicatat dan memotong master saldo cuti secara otomatis.");
        } catch (\Throwable $th) {
            $this->dangerBanner('Gagal mencatat cuti: ' . $th->getMessage());
        }
    }

    /**
     * Open Master Leave Types Modal.
     */
    #[On('open-manage-leave-types-modal')]
    public function openManageLeaveTypesModal(): void
    {
        $this->resetLeaveTypeForm();
        $this->isLeaveTypesModalOpen = true;
    }

    public function closeLeaveTypesModal(): void
    {
        $this->isLeaveTypesModalOpen = false;
        $this->resetLeaveTypeForm();
    }

    public function resetLeaveTypeForm(): void
    {
        $this->resetValidation();
        $this->isEditingLeaveType = false;
        $this->editingLeaveTypeId = null;
        $this->typeCode = '';
        $this->typeName = '';
        $this->typeDefaultDays = 1;
        $this->typeDeductsQuota = false;
        $this->typeRequiresAttachment = false;
        $this->typeIsActive = true;
        $this->typeDescription = '';
    }

    public function editLeaveType(int $id): void
    {
        $lt = LeaveType::findOrFail($id);
        $this->isEditingLeaveType = true;
        $this->editingLeaveTypeId = $lt->id;
        $this->typeCode = $lt->code;
        $this->typeName = $lt->name;
        $this->typeDefaultDays = (int) $lt->default_days;
        $this->typeDeductsQuota = (bool) $lt->deducts_annual_quota;
        $this->typeRequiresAttachment = (bool) $lt->requires_attachment;
        $this->typeIsActive = (bool) $lt->is_active;
        $this->typeDescription = $lt->description ?? '';
    }

    public function saveLeaveType(): void
    {
        $codeRule = 'required|string|max:50|unique:leave_types,code' . ($this->editingLeaveTypeId ? ',' . $this->editingLeaveTypeId : '');
        $this->validate([
            'typeCode' => $codeRule,
            'typeName' => 'required|string|max:100',
            'typeDefaultDays' => 'required|integer|min:1|max:365',
            'typeDeductsQuota' => 'boolean',
            'typeRequiresAttachment' => 'boolean',
            'typeIsActive' => 'boolean',
            'typeDescription' => 'nullable|string|max:500',
        ]);

        LeaveType::updateOrCreate(
            ['id' => $this->editingLeaveTypeId],
            [
                'code' => strtoupper(trim($this->typeCode)),
                'name' => trim($this->typeName),
                'default_days' => $this->typeDefaultDays,
                'deducts_annual_quota' => $this->typeDeductsQuota,
                'requires_attachment' => $this->typeRequiresAttachment,
                'is_active' => $this->typeIsActive,
                'description' => $this->typeDescription,
            ]
        );

        $this->resetLeaveTypeForm();
        $this->banner('Master Tipe Cuti berhasil diperbarui.');
    }

    public function toggleLeaveTypeStatus(int $id): void
    {
        $lt = LeaveType::findOrFail($id);
        $lt->update(['is_active' => !$lt->is_active]);
        $this->banner('Status tipe cuti ' . $lt->name . ' berhasil diubah.');
    }

    public function deleteLeaveType(int $id): void
    {
        $lt = LeaveType::findOrFail($id);
        if ($lt->code === 'ANNUAL') {
            $this->dangerBanner('Tipe Cuti Tahunan standar tidak dapat dihapus.');
            return;
        }
        $lt->delete();
        $this->banner('Tipe Cuti berhasil dihapus.');
    }

    /**
     * Export PDF report for leave tracking and management.
     */
    #[On('export-pdf')]
    public function exportPdf()
    {
        $employees = $this->getBaseQuery()->get();

        $summary = [
            'year' => $this->year,
            'division' => $this->division_id ? Division::find($this->division_id)?->name : 'Semua Divisi',
            'total_employees' => $employees->count(),
            'total_quota' => $employees->sum(fn($u) => $u->leaveBalanceForYear($this->year)->total_quota),
            'total_used' => $employees->sum(fn($u) => $u->leaveBalanceForYear($this->year)->used_quota),
            'total_remaining' => $employees->sum(fn($u) => $u->leaveBalanceForYear($this->year)->remaining_quota),
        ];

        $pdf = Pdf::loadView('admin.leave.report-pdf', [
            'employees' => $employees,
            'summary' => $summary,
            'year' => $this->year,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "Laporan_Manajemen_Cuti_{$this->year}.pdf"
        );
    }

    protected function getBaseQuery(): Builder
    {
        return User::where('group', 'user')
            ->whereIn('status', ['active', 'suspend'])
            ->when($this->division_id, fn($q) => $q->where('division_id', $this->division_id))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('nip', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->with(['division', 'jobTitle', 'salary', 'leaveBalances' => fn($q) => $q->where('year', $this->year)])
            ->orderBy('name');
    }

    public function render()
    {
        $query = $this->getBaseQuery();
        $allEmployeesForStats = $query->get();

        // Calculate Overview Statistics
        $totalEmployees = $allEmployeesForStats->count();
        $totalQuotaAllocated = 0;
        $totalUsedDays = 0;
        $totalRemainingDays = 0;
        $criticalCount = 0;
        $emptyCount = 0;

        foreach ($allEmployeesForStats as $emp) {
            $bal = $emp->leaveBalanceForYear($this->year);
            $totalQuotaAllocated += $bal->total_quota;
            $totalUsedDays += $bal->used_quota;
            $totalRemainingDays += $bal->remaining_quota;

            if ($bal->remaining_quota <= 0) {
                $emptyCount++;
            } elseif ($bal->remaining_quota <= 3) {
                $criticalCount++;
            }
        }

        // Live count employees currently taking leave today
        $todayStr = date('Y-m-d');
        $onLeaveToday = Attendance::where('date', $todayStr)
            ->whereIn('status', ['leave', 'special-leaves'])
            ->with(['user.division', 'user.jobTitle'])
            ->get();

        // Filter by quota_status if set
        $filteredCollection = $allEmployeesForStats;
        if ($this->quota_status !== 'all') {
            $filteredCollection = $filteredCollection->filter(function ($emp) {
                $rem = $emp->leaveBalanceForYear($this->year)->remaining_quota;
                return match ($this->quota_status) {
                    'safe' => $rem > 3,
                    'critical' => $rem > 0 && $rem <= 3,
                    'empty' => $rem === 0,
                    'minus' => $rem < 0,
                    default => true,
                };
            });
        }

        // Paginate manually from filtered collection or database
        $currentPage = $this->getPage();
        $items = $filteredCollection->slice(($currentPage - 1) * $this->perPage, $this->perPage)->all();
        $employeesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filteredCollection->count(),
            $this->perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $divisions = Division::orderBy('name')->get();
        $allEmployeesList = User::where('group', 'user')
            ->whereIn('status', ['active', 'suspend'])
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'division_id']);

        $masterLeaveTypes = LeaveType::orderBy('id')->get();

        return view('livewire.admin.leave-management-component', [
            'employees' => $employeesPaginated,
            'divisions' => $divisions,
            'allEmployeesList' => $allEmployeesList,
            'masterLeaveTypes' => $masterLeaveTypes,
            'totalEmployees' => $totalEmployees,
            'totalQuotaAllocated' => $totalQuotaAllocated,
            'totalUsedDays' => $totalUsedDays,
            'totalRemainingDays' => $totalRemainingDays,
            'criticalCount' => $criticalCount,
            'emptyCount' => $emptyCount,
            'onLeaveToday' => $onLeaveToday,
        ])->layout('layouts.app');
    }
}
