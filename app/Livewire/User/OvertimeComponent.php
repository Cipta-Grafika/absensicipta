<?php

namespace App\Livewire\User;

use App\Models\Overtime;
use App\Models\OvertimeRate;
use App\Services\AttendanceScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class OvertimeComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public ?string $month = null;
    public $overtime_date;
    public $start_time;
    public $end_time;
    public $break = '';
    public $reason;
    public $modalError;
    public $selectedDateDisplay = '';
    public $activeCalendarDate = null;

    public $selectedOvertime = null;
    public ?int $editingOvertimeId = null;
    public $isDateModalOpen = false;
    public $isDetailModalOpen = false;
    public $perPage = 10;

    public function mount()
    {
        $this->month = date('Y-m');
    }

    public function updatingMonth()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatedOvertimeDate($val)
    {
        if ($val) {
            $this->activeCalendarDate = Carbon::parse($val)->format('Y-m-d');
        }
    }

    protected $rules = [
        'overtime_date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
        'break' => 'nullable|string|max:10',
        'reason' => 'required|string|max:1000',
    ];

    protected $messages = [
        'overtime_date.required' => 'Tanggal lembur wajib dipilih.',
        'start_time.required' => 'Jam mulai wajib diisi.',
        'start_time.date_format' => 'Format jam mulai harus HH:MM (contoh: 10:30).',
        'end_hour.required' => 'Jam selesai wajib diisi.',
        'end_time.date_format' => 'Format jam selesai harus HH:MM (contoh: 17:30).',
        'reason.required' => 'Alasan / kegiatan lembur wajib diisi.',
    ];

    public function render()
    {
        $user = Auth::user();
        $selectedMonth = $this->month ?: date('Y-m');
        $date = Carbon::parse($selectedMonth);

        $start = Carbon::parse($selectedMonth)->startOfMonth();
        $end = Carbon::parse($selectedMonth)->endOfMonth();
        $dates = $start->range($end)->toArray();

        // Fetch overtime records for calendar visualization
        $monthOvertimes = Overtime::where('employee_id', $user->id)
            ->whereBetween('overtime_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        // Paginated table query strictly for selected month
        $query = Overtime::where('employee_id', $user->id)
            ->whereBetween('overtime_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('overtime_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($this->perPage === 'all') {
            $overtimes = $query->paginate($query->count() > 0 ? $query->count() : 1);
        } else {
            $overtimes = $query->paginate($this->perPage);
        }

        $offDays = AttendanceScheduleService::getUserOffDays($user);

        return view('livewire.user.overtime-component', [
            'overtimes' => $overtimes,
            'monthOvertimes' => $monthOvertimes,
            'dates' => $dates,
            'start' => $start,
            'end' => $end,
            'offDays' => $offDays,
            'month' => $selectedMonth,
            'activeCalendarDate' => $this->activeCalendarDate,
            'isDateModalOpen' => $this->isDateModalOpen,
            'isDetailModalOpen' => $this->isDetailModalOpen,
            'editingOvertimeId' => $this->editingOvertimeId,
            'overtime_date' => $this->overtime_date,
            'selectedDateDisplay' => $this->selectedDateDisplay,
            'modalError' => $this->modalError,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'reason' => $this->reason,
            'selectedOvertime' => $this->selectedOvertime,
        ])->layout('layouts.app');
    }

    public function showDetail($id)
    {
        $overtime = Overtime::with(['employee.division', 'employee.jobTitle', 'employee.salary', 'approver'])
            ->where('employee_id', Auth::id())
            ->find($id);

        if ($overtime) {
            $this->selectedOvertime = $overtime;
            $this->activeCalendarDate = $overtime->overtime_date ? Carbon::parse($overtime->overtime_date)->format('Y-m-d') : null;
            $this->selectedDateDisplay = $overtime->overtime_date ? Carbon::parse($overtime->overtime_date)->locale('id')->isoFormat('dddd, DD MMMM YYYY') : '';
            $this->isDetailModalOpen = true;
        }
    }

    public function editOvertime($id)
    {
        $overtime = Overtime::where('employee_id', Auth::id())->find($id);

        if (!$overtime) {
            $this->dangerBanner('Data pengajuan lembur tidak ditemukan.');
            return;
        }

        if ($overtime->status !== 'pending') {
            $this->dangerBanner('Pengajuan lembur tidak dapat diedit karena status sudah diproses (' . ucfirst($overtime->status) . ').');
            return;
        }

        $this->editingOvertimeId = $overtime->id;
        $this->overtime_date = $overtime->overtime_date ? Carbon::parse($overtime->overtime_date)->format('Y-m-d') : '';
        $this->start_time = $overtime->start_time ? Carbon::parse($overtime->start_time)->format('H:i') : '';
        $this->end_time = $overtime->end_time ? Carbon::parse($overtime->end_time)->format('H:i') : '';
        $this->break = $overtime->break ?? '';
        $this->reason = $overtime->reason ?? '';
        $this->modalError = null;

        $this->activeCalendarDate = $this->overtime_date;
        $this->selectedDateDisplay = $this->overtime_date ? Carbon::parse($this->overtime_date)->locale('id')->isoFormat('dddd, DD MMMM YYYY') : '';

        $this->isDetailModalOpen = false;
        $this->isDateModalOpen = true;
    }

    public function handleDateClick($dateString)
    {
        $user = Auth::user();
        $formattedDate = Carbon::parse($dateString)->format('Y-m-d');

        $existing = Overtime::with(['employee.division', 'employee.jobTitle', 'employee.salary', 'approver'])
            ->where('employee_id', $user->id)
            ->where('overtime_date', $formattedDate)
            ->first();

        $this->activeCalendarDate = $formattedDate;
        $this->selectedDateDisplay = Carbon::parse($formattedDate)->locale('id')->isoFormat('dddd, DD MMMM YYYY');

        if ($existing) {
            // Case 1: Existing Overtime -> Open Detail Modal
            $this->selectedOvertime = $existing;
            $this->isDetailModalOpen = true;
        } else {
            // Case 2: No Existing Overtime -> Open Submission Modal
            $this->resetInputFields();
            $this->overtime_date = $formattedDate;
            $this->activeCalendarDate = $formattedDate;
            $this->selectedDateDisplay = Carbon::parse($formattedDate)->locale('id')->isoFormat('dddd, DD MMMM YYYY');
            $this->isDateModalOpen = true;
        }
    }

    public function closeDateModal()
    {
        $this->isDateModalOpen = false;
        $this->activeCalendarDate = null;
        $this->resetInputFields();
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedOvertime = null;
        $this->activeCalendarDate = null;
        $this->selectedDateDisplay = '';
    }

    private function resetInputFields()
    {
        $this->editingOvertimeId = null;
        $this->overtime_date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->break = '';
        $this->reason = '';
        $this->modalError = null;
        $this->selectedDateDisplay = '';
    }

    public function submitDateModal()
    {
        $this->saveOvertime();
        if (!$this->modalError) {
            $this->closeDateModal();
        }
    }

    private function saveOvertime()
    {
        $this->modalError = null;
        $this->validate();

        if ($this->editingOvertimeId) {
            // Edit existing pending overtime
            $overtime = Overtime::where('employee_id', Auth::id())->find($this->editingOvertimeId);

            if (!$overtime) {
                $this->modalError = 'Data pengajuan lembur tidak ditemukan.';
                return;
            }

            if ($overtime->status !== 'pending') {
                $this->modalError = 'Pengajuan lembur tidak dapat diedit karena status sudah diproses (' . ucfirst($overtime->status) . ').';
                return;
            }

            // Enforce 1 overtime request per date rule (excluding current record)
            $exists = Overtime::where('employee_id', Auth::id())
                ->where('overtime_date', $this->overtime_date)
                ->where('id', '!=', $this->editingOvertimeId)
                ->exists();

            if ($exists) {
                $this->modalError = 'Anda sudah memiliki pengajuan lembur lain pada tanggal ini (1 tanggal hanya 1 pengajuan lembur).';
                return;
            }

            $tempOvertime = new Overtime([
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'break' => $this->break ? trim($this->break) : null,
            ]);

            $duration = $tempOvertime->calculateDuration();

            if ($duration <= 0) {
                $this->modalError = 'Durasi lembur tidak valid. Pastikan jam selesai lebih besar dari jam mulai.';
                return;
            }

            $overtime->update([
                'overtime_date' => $this->overtime_date,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'break' => $this->break ? trim($this->break) : null,
                'reason' => $this->reason,
                'duration_hours' => $duration,
            ]);

            $this->banner('Pengajuan lembur berhasil diperbarui.');
        } else {
            // Enforce 1 overtime request per date rule
            $exists = Overtime::where('employee_id', Auth::id())
                ->where('overtime_date', $this->overtime_date)
                ->exists();

            if ($exists) {
                $this->modalError = 'Anda sudah memiliki pengajuan lembur pada tanggal ini (1 tanggal hanya 1 pengajuan lembur).';
                return;
            }

            // Instantiate Overtime to calculate duration before saving
            $overtime = new Overtime([
                'employee_id' => Auth::id(),
                'overtime_date' => $this->overtime_date,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'break' => $this->break ? trim($this->break) : null,
                'reason' => $this->reason,
                'status' => 'pending',
            ]);

            $duration = $overtime->calculateDuration();

            if ($duration <= 0) {
                $this->modalError = 'Durasi lembur tidak valid. Pastikan jam selesai lebih besar dari jam mulai.';
                return;
            }

            $overtime->duration_hours = $duration;
            $overtime->save();

            $this->banner('Pengajuan lembur berhasil dikirim dan sedang menunggu persetujuan.');
        }
    }
}

