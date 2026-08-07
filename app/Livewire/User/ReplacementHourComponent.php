<?php

namespace App\Livewire\User;

use App\Models\ReplacementHour;
use App\Models\Shift;
use App\Models\Attendance;
use App\Services\AttendanceScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class ReplacementHourComponent extends Component
{
    use WithFileUploads, InteractsWithBanner, WithPagination;

    public ?string $month = null;
    public $replaced_date;
    public $replacement_date;
    public $start_hour;
    public $end_hour;
    public $shift_id;
    public $reason;
    public $attachment;
    public $modalError;
    public $selectedDateDisplay = '';
    public $activeCalendarDate = null;

    public $selectedReplacement = null;
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

    public function updatedReplacedDate($val)
    {
        if ($val) {
            $this->activeCalendarDate = Carbon::parse($val)->format('Y-m-d');
        }
    }

    protected $rules = [
        'replaced_date' => 'required|date',
        'replacement_date' => 'required|date',
        'start_hour' => 'required|date_format:H:i',
        'end_hour' => 'required|date_format:H:i',
        'shift_id' => 'required|exists:shifts,id',
        'reason' => 'required|string|max:1000',
        'attachment' => 'required|image|max:2048', // maksimal 2MB
    ];

    protected $messages = [
        'replaced_date.required' => 'Tanggal absen yang diganti wajib dipilih.',
        'replacement_date.required' => 'Tanggal penggantian wajib dipilih.',
        'start_hour.required' => 'Jam mulai wajib diisi.',
        'start_hour.date_format' => 'Format jam mulai harus HH:MM (contoh: 10:30).',
        'end_hour.required' => 'Jam selesai wajib diisi.',
        'end_hour.date_format' => 'Format jam selesai harus HH:MM (contoh: 17:30).',
        'shift_id.required' => 'Shift yang digantikan wajib dipilih.',
        'reason.required' => 'Alasan pengajuan ganti jam wajib diisi.',
        'attachment.required' => 'Lampiran bukti (foto/gambar) wajib diunggah.',
        'attachment.image' => 'File lampiran harus berupa gambar (JPG, PNG, dll).',
        'attachment.max' => 'Ukuran lampiran maksimal 2MB.',
    ];

    public function render()
    {
        $user = Auth::user();
        $selectedMonth = $this->month ?: date('Y-m');
        $date = Carbon::parse($selectedMonth);

        $start = Carbon::parse($selectedMonth)->startOfMonth();
        $end = Carbon::parse($selectedMonth)->endOfMonth();
        $dates = $start->range($end)->toArray();

        // Fetch replacement hour records strictly by replaced_date (IMP date)
        $monthReplacements = ReplacementHour::with(['shift', 'approver'])
            ->where('user_id', $user->id)
            ->whereBetween('replaced_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        // Fetch IMP attendance dates for the selected month to highlight IMP date boxes
        $monthImpDates = Attendance::where('user_id', $user->id)
            ->where('status', 'imp')
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Query history table strictly for selected month by replaced_date
        $query = ReplacementHour::with(['shift', 'approver'])
            ->where('user_id', $user->id)
            ->whereBetween('replaced_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('replaced_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($this->perPage === 'all') {
            $replacements = $query->paginate($query->count() > 0 ? $query->count() : 1);
        } else {
            $replacements = $query->paginate($this->perPage);
        }

        // Retrieve user accessible shifts: Division shifts first, then global shifts
        $shifts = Shift::forUser($user)
            ->orderByRaw('CASE WHEN division_id = ? THEN 0 ELSE 1 END', [$user->division_id ?? 0])
            ->orderBy('name', 'asc')
            ->get();

        $offDays = AttendanceScheduleService::getUserOffDays($user);

        return view('livewire.user.replacement-hour-component', [
            'replacements' => $replacements,
            'monthReplacements' => $monthReplacements,
            'monthImpDates' => $monthImpDates,
            'dates' => $dates,
            'start' => $start,
            'end' => $end,
            'offDays' => $offDays,
            'shifts' => $shifts,
            'month' => $selectedMonth,
            'activeCalendarDate' => $this->activeCalendarDate,
            'isDateModalOpen' => $this->isDateModalOpen,
            'isDetailModalOpen' => $this->isDetailModalOpen,
            'replaced_date' => $this->replaced_date,
            'replacement_date' => $this->replacement_date,
            'selectedDateDisplay' => $this->selectedDateDisplay,
            'modalError' => $this->modalError,
            'start_hour' => $this->start_hour,
            'end_hour' => $this->end_hour,
            'shift_id' => $this->shift_id,
            'reason' => $this->reason,
            'attachment' => $this->attachment,
            'selectedReplacement' => $this->selectedReplacement,
            'errors' => session('errors', new \Illuminate\Support\ViewErrorBag),
        ])->layout('layouts.app');
    }

    public function handleDateClick($dateString)
    {
        $user = Auth::user();
        $formattedDate = Carbon::parse($dateString)->format('Y-m-d');

        // Only match strictly by replaced_date (the IMP date)
        $existing = ReplacementHour::with(['shift', 'approver'])
            ->where('user_id', $user->id)
            ->where('replaced_date', $formattedDate)
            ->first();

        $this->activeCalendarDate = $formattedDate;
        $this->selectedDateDisplay = Carbon::parse($formattedDate)->locale('id')->isoFormat('dddd, DD MMMM YYYY');

        if ($existing) {
            $this->selectedReplacement = $existing;
            $this->isDetailModalOpen = true;
        } else {
            $this->resetInputFields();
            $this->replaced_date = $formattedDate;
            $this->replacement_date = Carbon::now()->format('Y-m-d');

            // Default shift: prioritize division shift first
            $shifts = Shift::forUser($user)
                ->orderByRaw('CASE WHEN division_id = ? THEN 0 ELSE 1 END', [$user->division_id ?? 0])
                ->orderBy('name', 'asc')
                ->get();
            $defaultShift = $shifts->where('division_id', $user->division_id)->first() ?? $shifts->first();
            $this->shift_id = $defaultShift ? $defaultShift->id : '';

            $this->activeCalendarDate = $formattedDate;
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
        $this->selectedReplacement = null;
        $this->activeCalendarDate = null;
        $this->selectedDateDisplay = '';
    }

    private function resetInputFields()
    {
        $this->replaced_date = '';
        $this->replacement_date = '';
        $this->start_hour = '';
        $this->end_hour = '';
        $this->shift_id = '';
        $this->reason = '';
        $this->attachment = null;
        $this->modalError = null;
        $this->selectedDateDisplay = '';
    }

    public function submitDateModal()
    {
        $this->saveReplacement();
        if (!$this->modalError) {
            $this->closeDateModal();
        }
    }

    private function saveReplacement()
    {
        $this->modalError = null;
        $this->validate();

        // Check if user has an IMP attendance on replaced_date
        $hasImp = Attendance::where('user_id', Auth::id())
            ->where('date', $this->replaced_date)
            ->where('status', 'imp')
            ->exists();

        if (!$hasImp) {
            $this->modalError = 'Tanggal absen yang diganti (' . Carbon::parse($this->replaced_date)->format('d/m/Y') . ') tidak memiliki status IMP (Izin Meninggalkan Pekerjaan). Anda hanya bisa mengganti jam untuk hari di mana Anda melakukan IMP!';
            return;
        }

        $attachmentPath = null;
        if ($this->attachment) {
            $attachmentPath = $this->attachment->store('attachments', 'public');
        }

        ReplacementHour::create([
            'user_id' => Auth::id(),
            'replaced_date' => $this->replaced_date,
            'replacement_date' => $this->replacement_date,
            'start_hour' => $this->start_hour,
            'end_hour' => $this->end_hour,
            'shift_id' => $this->shift_id,
            'reason' => $this->reason,
            'attachment' => $attachmentPath,
            'status' => 'pending',
        ]);

        $this->banner('Pengajuan ganti jam berhasil dikirim dan sedang menunggu persetujuan.');
    }
}
