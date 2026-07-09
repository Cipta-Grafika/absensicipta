<?php

namespace App\Livewire\User;

use App\Models\ReplacementHour;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Laravel\Jetstream\InteractsWithBanner;

use Livewire\WithPagination;

class ReplacementHourComponent extends Component
{
    use WithFileUploads, InteractsWithBanner, WithPagination;

    public $replaced_date;
    public $replacement_date;
    public $start_hour;
    public $end_hour;
    public $shift_id;
    public $reason;
    public $attachment;

    public $isModalOpen = false;
    public $perPage = 10;

    public function updatingPerPage()
    {
        $this->resetPage();
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

    public function render()
    {
        $now = Carbon::now();
        $query = ReplacementHour::with('shift')
            ->where('user_id', Auth::id())
            ->whereMonth('replaced_date', $now->month)
            ->whereYear('replaced_date', $now->year)
            ->orderBy('created_at', 'desc');

        if ($this->perPage === 'all') {
            $replacements = $query->paginate($query->count() > 0 ? $query->count() : 1);
        } else {
            $replacements = $query->paginate($this->perPage);
        }
            
        $shifts = \App\Models\Shift::all();

        return view('livewire.user.replacement-hour-component', [
            'replacements' => $replacements,
            'shifts' => $shifts
        ])->layout('layouts.app');
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
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
    }

    public function submit()
    {
        $this->validate();

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

        $this->closeModal();
    }
}
