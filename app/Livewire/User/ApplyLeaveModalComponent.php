<?php

namespace App\Livewire\User;

use App\Models\Attendance;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Laravel\Jetstream\InteractsWithBanner;

class ApplyLeaveModalComponent extends Component
{
    use WithFileUploads, InteractsWithBanner;

    public $status = 'excused';
    public $note;
    public $from;
    public $to;
    public $imp_duration_minutes;
    public $shift_id;
    public $attachment;
    public $lat;
    public $lng;

    public $isModalOpen = false;

    protected $listeners = ['open-apply-leave-modal' => 'openModal'];

    public function mount()
    {
        $this->from = date('Y-m-d');
    }

    public function openModal()
    {
        $this->resetErrorBag();
        $this->reset(['status', 'note', 'to', 'imp_duration_minutes', 'shift_id', 'attachment', 'lat', 'lng']);
        $this->status = 'excused';
        $this->from = date('Y-m-d');
        $this->isModalOpen = true;
    }

    protected function rules()
    {
        return [
            'status' => ['required', 'in:excused,sick,leave,wfh,imp,special-leaves'],
            'note' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'imp_duration_minutes' => ['nullable', 'string', 'regex:/^([0-9]+):([0-5][0-9])$/', 'required_if:status,imp'],
            'shift_id' => ['nullable', 'exists:shifts,id', 'required_if:status,imp'],
            'attachment' => ['nullable', 'file', 'max:3072'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ];
    }

    public function submit()
    {
        $this->validate();

        try {
            $newAttachment = null;
            if ($this->attachment) {
                $newAttachment = $this->attachment->storePublicly(
                    'attachments',
                    ['disk' => config('jetstream.attachment_disk')]
                );
            }

            $fromDate = Carbon::parse($this->from);
            $toDate = Carbon::parse($this->to ?: $this->from);

            $parsedImpDurationMinutes = null;
            if ($this->status === 'imp' && $this->imp_duration_minutes) {
                list($h, $m) = explode(':', $this->imp_duration_minutes);
                $parsedImpDurationMinutes = ((int)$h * 60) + (int)$m;
                if ($parsedImpDurationMinutes <= 0) {
                    $this->addError('imp_duration_minutes', 'Durasi IMP tidak boleh 0.');
                    return;
                }
            }

            $hasPresentOrLate = Attendance::where('user_id', Auth::user()->id)
                ->whereBetween('date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
                ->whereIn('status', ['present', 'late'])
                ->first();

            if ($hasPresentOrLate) {
                if ($hasPresentOrLate->status === 'late') {
                    $this->addError('from', 'Anda tidak dapat mengajukan izin/status lain pada tanggal ' . Carbon::parse($hasPresentOrLate->date)->format('d/m/Y') . ' karena Anda sudah tercatat Terlambat pada hari tersebut.');
                    return;
                } else if ($hasPresentOrLate->status === 'present' && $this->status !== 'imp') {
                    $this->addError('from', 'Anda tidak dapat mengajukan izin/status lain pada tanggal ' . Carbon::parse($hasPresentOrLate->date)->format('d/m/Y') . ' karena Anda sudah tercatat Hadir pada hari tersebut. Anda hanya dapat mengajukan Izin Meninggalkan Pekerjaan (IMP).');
                    return;
                }
            }

            $fromDate->range($toDate)
                ->forEach(function (Carbon $date) use ($newAttachment, $parsedImpDurationMinutes) {
                    $existing = Attendance::where('user_id', Auth::user()->id)
                        ->where('date', $date->format('Y-m-d'))
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'status' => $this->status,
                            'note' => $this->note,
                            'attachment' => $newAttachment ?? $existing->attachment,
                            'latitude' => $this->lat ? doubleval($this->lat) : $existing->latitude,
                            'longitude' => $this->lng ? doubleval($this->lng) : $existing->longitude,
                            'imp_duration_minutes' => $this->status === 'imp' ? $parsedImpDurationMinutes : $existing->imp_duration_minutes,
                            'shift_id' => $this->status === 'imp' ? $this->shift_id : $existing->shift_id,
                        ]);
                    } else {
                        Attendance::create([
                            'user_id' => Auth::user()->id,
                            'status' => $this->status,
                            'date' => $date->format('Y-m-d'),
                            'note' => $this->note,
                            'attachment' => $newAttachment ?? null,
                            'latitude' => $this->lat ? doubleval($this->lat) : null,
                            'longitude' => $this->lng ? doubleval($this->lng) : null,
                            'imp_duration_minutes' => $this->status === 'imp' ? $parsedImpDurationMinutes : null,
                            'shift_id' => $this->status === 'imp' ? $this->shift_id : null,
                        ]);
                    }
                });

            Attendance::clearUserAttendanceCache(Auth::user(), Carbon::parse($this->from));
            if (!Carbon::parse($this->from)->isSameMonth($toDate)) {
                Attendance::clearUserAttendanceCache(Auth::user(), $toDate);
            }

            $this->isModalOpen = false;
            $this->banner('Pengajuan izin berhasil dibuat.');
            $this->dispatch('leave-submitted');

        } catch (\Throwable $th) {
            $this->dangerBanner($th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.user.apply-leave-modal-component', [
            'shifts' => Shift::all()
        ]);
    }
}
