<?php

namespace App\Livewire\User;

use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class OvertimeComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $overtime_date;
    public $start_time;
    public $end_time;
    public $reason;
    public $modalError;

    public $isModalOpen = false;
    public $perPage = 10;

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    protected $rules = [
        'overtime_date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
        'reason' => 'required|string|max:1000',
    ];

    public function render()
    {
        $now = Carbon::now();
        $query = Overtime::where('employee_id', Auth::id())
            ->whereMonth('overtime_date', $now->month)
            ->whereYear('overtime_date', $now->year)
            ->orderBy('created_at', 'desc');

        if ($this->perPage === 'all') {
            $overtimes = $query->paginate($query->count() > 0 ? $query->count() : 1);
        } else {
            $overtimes = $query->paginate($this->perPage);
        }

        return view('livewire.user.overtime-component', [
            'overtimes' => $overtimes,
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
        $this->overtime_date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->reason = '';
        $this->modalError = null;
    }

    public function submit()
    {
        $this->modalError = null;
        $this->validate();

        // Instantiate Overtime to calculate duration before saving
        $overtime = new Overtime([
            'employee_id' => Auth::id(),
            'overtime_date' => $this->overtime_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
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
        $this->closeModal();
    }
}
