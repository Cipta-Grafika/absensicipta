<?php

namespace App\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Division;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\FlexibleDeductionProgram;
use App\Models\FlexibleDeduction;
use App\Services\SavingTransactionService;
use Laravel\Jetstream\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FlexibleDeductionComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $activeTab = 'master'; // 'master' or 'history'
    public $search = '';
    public $division = '';
    public $status = '';
    public $selected_period_month = '';
    public $selected_program_id = '';

    // Modal Program Creation
    public $isProgramModalOpen = false;
    public $program_name = '';
    public $program_period_month = '';
    public $program_description = '';

    // Modal Set Employee Deduction
    public $isDeductionModalOpen = false;
    public $selectedEmployee = null;
    public $deduction_user_id = '';
    public $deduction_amount = 0;
    public $deduction_source = 'payroll';
    public $deduction_notes = '';

    // Real-time Syirkah Balance for Selected Employee in Modal
    public $employee_syirkah_mandatory = 0;
    public $employee_syirkah_secondary = 0;

    // Quick Batch Set
    public $isBatchModalOpen = false;
    public $batch_amount = 0;
    public $batch_source = 'payroll';

    protected function rules()
    {
        return [
            'program_name' => 'required|string|max:255',
            'program_period_month' => 'required|date_format:Y-m',
            'program_description' => 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->selected_period_month = now()->format('Y-m');
        $this->program_period_month = now()->format('Y-m');
        $this->ensureValidProgramSelected();
    }

    public function ensureValidProgramSelected()
    {
        $programs = FlexibleDeductionProgram::where('period_month', $this->selected_period_month)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($programs->isNotEmpty()) {
            if (!$this->selected_program_id || !$programs->contains('id', $this->selected_program_id)) {
                $this->selected_program_id = (string) $programs->first()->id;
            }
        } else {
            $this->selected_program_id = '';
        }
    }

    public function updatedSelectedPeriodMonth()
    {
        $this->resetPage();
        $this->ensureValidProgramSelected();
    }

    public function updatedSelectedProgramId()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDivision()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function openProgramModal()
    {
        $this->resetValidation();
        $this->program_name = '';
        $this->program_period_month = $this->selected_period_month ?: now()->format('Y-m');
        $this->program_description = '';
        $this->isProgramModalOpen = true;
    }

    public function closeProgramModal()
    {
        $this->isProgramModalOpen = false;
    }

    public function storeProgram()
    {
        $this->validate([
            'program_name' => 'required|string|max:255',
            'program_period_month' => 'required|date_format:Y-m',
            'program_description' => 'nullable|string',
        ]);

        $program = FlexibleDeductionProgram::create([
            'name' => $this->program_name,
            'period_month' => $this->program_period_month,
            'description' => $this->program_description,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        $this->selected_period_month = $this->program_period_month;
        $this->selected_program_id = (string) $program->id;

        $this->banner('Program keperluan potongan fleksibel berhasil dibuat.');
        $this->closeProgramModal();
    }

    public function loadEmployeeSyirkahBalance(string $userId)
    {
        $depMan = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('mandatory_amount');
        $wdMan = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('mandatory_amount');
        $this->employee_syirkah_mandatory = max(0.0, $depMan - $wdMan);

        $depSec = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('secondary_amount');
        $wdSec = (float) SavingTransaction::where('user_id', $userId)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('secondary_amount');
        $this->employee_syirkah_secondary = max(0.0, $depSec - $wdSec);
    }

    public function openDeductionModal(string $userId)
    {
        $this->ensureValidProgramSelected();

        if (!$this->selected_program_id) {
            $this->openProgramModal();
            $this->banner('Silakan buat program keperluan potongan untuk bulan ini terlebih dahulu.');
            return;
        }

        $user = User::onlyWorkingEmployee()->with(['division', 'jobTitle'])->findOrFail($userId);
        $this->selectedEmployee = $user;
        $this->deduction_user_id = $user->id;

        $this->loadEmployeeSyirkahBalance($user->id);

        $existing = FlexibleDeduction::where('program_id', $this->selected_program_id)
            ->where('user_id', $userId)
            ->where('period_month', $this->selected_period_month)
            ->first();

        if ($existing) {
            $this->deduction_amount = $existing->amount;
            $this->deduction_source = $existing->deduction_source ?? 'payroll';
            $this->deduction_notes = $existing->notes;
        } else {
            $this->deduction_amount = 0;
            $this->deduction_source = 'payroll';
            $this->deduction_notes = '';
        }

        $this->isDeductionModalOpen = true;
    }

    public function closeDeductionModal()
    {
        $this->isDeductionModalOpen = false;
        $this->reset(['selectedEmployee', 'deduction_user_id', 'deduction_amount', 'deduction_source', 'deduction_notes', 'employee_syirkah_mandatory', 'employee_syirkah_secondary']);
    }

    public function saveDeduction()
    {
        $this->ensureValidProgramSelected();

        if (!$this->selected_program_id || !$this->deduction_user_id) {
            return;
        }

        $this->validate([
            'deduction_amount' => 'required|numeric|min:0',
            'deduction_source' => 'required|in:payroll,syirkah_mandatory,syirkah_secondary,syirkah_all',
            'deduction_notes'  => 'nullable|string|max:255',
        ]);

        // Validate Syirkah balance sufficiency if amount > 0 and using Syirkah
        if ($this->deduction_amount > 0) {
            if ($this->deduction_source === 'syirkah_mandatory' && $this->deduction_amount > $this->employee_syirkah_mandatory) {
                $this->addError('deduction_amount', 'Nominal potongan melebihi Saldo Syirkah Wajib karyawan (Rp ' . number_format($this->employee_syirkah_mandatory, 0, ',', '.') . ').');
                return;
            }

            if ($this->deduction_source === 'syirkah_secondary' && $this->deduction_amount > $this->employee_syirkah_secondary) {
                $this->addError('deduction_amount', 'Nominal potongan melebihi Saldo Syirkah SSR karyawan (Rp ' . number_format($this->employee_syirkah_secondary, 0, ',', '.') . ').');
                return;
            }

            if ($this->deduction_source === 'syirkah_all' && $this->deduction_amount > ($this->employee_syirkah_mandatory + $this->employee_syirkah_secondary)) {
                $this->addError('deduction_amount', 'Nominal potongan melebihi Total Saldo Syirkah karyawan (Rp ' . number_format($this->employee_syirkah_mandatory + $this->employee_syirkah_secondary, 0, ',', '.') . ').');
                return;
            }
        }

        FlexibleDeduction::updateOrCreate(
            [
                'program_id'   => $this->selected_program_id,
                'user_id'      => $this->deduction_user_id,
                'period_month' => $this->selected_period_month,
            ],
            [
                'amount'           => $this->deduction_amount,
                'deduction_source' => $this->deduction_source,
                'notes'            => $this->deduction_notes,
            ]
        );

        $this->banner('Nominal potongan karyawan berhasil disimpan.');
        $this->closeDeductionModal();
    }

    public function openBatchModal()
    {
        $this->ensureValidProgramSelected();

        if (!$this->selected_program_id) {
            $this->dangerBanner('Silakan buat atau pilih program potongan terlebih dahulu.');
            return;
        }
        $this->batch_amount = 0;
        $this->batch_source = 'payroll';
        $this->isBatchModalOpen = true;
    }

    public function closeBatchModal()
    {
        $this->isBatchModalOpen = false;
    }

    public function applyBatchAmount()
    {
        $this->ensureValidProgramSelected();

        if (!$this->selected_program_id) {
            return;
        }

        $this->validate([
            'batch_amount' => 'required|numeric|min:0',
            'batch_source' => 'required|in:payroll,syirkah_mandatory,syirkah_secondary,syirkah_all',
        ]);

        $users = User::onlyWorkingEmployee()
            ->when($this->division, function ($q) {
                $q->where('division_id', $this->division);
            })
            ->get();

        DB::transaction(function () use ($users) {
            foreach ($users as $user) {
                FlexibleDeduction::updateOrCreate(
                    [
                        'program_id'   => $this->selected_program_id,
                        'user_id'      => $user->id,
                        'period_month' => $this->selected_period_month,
                    ],
                    [
                        'amount'           => $this->batch_amount,
                        'deduction_source' => $this->batch_source,
                    ]
                );
            }
        });

        $count = $users->count();
        $this->banner("Berhasil menyetel potongan Rp " . number_format($this->batch_amount, 0, ',', '.') . " untuk {$count} karyawan.");
        $this->closeBatchModal();
    }

    public function resetMonthDeductions()
    {
        $this->ensureValidProgramSelected();

        if (!$this->selected_program_id) {
            return;
        }

        FlexibleDeduction::where('program_id', $this->selected_program_id)
            ->where('period_month', $this->selected_period_month)
            ->update(['amount' => 0]);

        $this->banner('Seluruh nominal potongan untuk program bulan ini berhasil direset menjadi Rp 0.');
    }

    public function deleteProgram(string $programId)
    {
        $program = FlexibleDeductionProgram::findOrFail($programId);
        $program->delete();

        $this->ensureValidProgramSelected();
        $this->banner('Program potongan fleksibel berhasil dihapus.');
    }

    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSyirkah || auth()->user()->isSuperadmin || auth()->user()->isOwner, 403);

        $programs = FlexibleDeductionProgram::where('period_month', $this->selected_period_month)
            ->orderBy('created_at', 'desc')
            ->get();

        // Ensure selected program matches available programs for this month
        if ($programs->isNotEmpty()) {
            if (!$this->selected_program_id || !$programs->contains('id', $this->selected_program_id)) {
                $this->selected_program_id = (string) $programs->first()->id;
            }
        } else {
            $this->selected_program_id = '';
        }

        $currentProgram = null;
        if ($this->selected_program_id) {
            $currentProgram = $programs->firstWhere('id', $this->selected_program_id);
        }

        // Fetch master employees with their flexible deduction for selected program & month
        $employees = User::onlyEmployee()
            ->when($this->status, function ($query) {
                if ($this->status === 'all') {
                    return $query;
                }
                return $query->where('status', $this->status);
            }, function ($query) {
                return $query->whereIn('status', ['active', 'suspend']);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nip', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->division, function ($query) {
                $query->where('division_id', $this->division);
            })
            ->with(['division', 'jobTitle', 'flexibleDeductions' => function ($q) {
                $q->where('program_id', $this->selected_program_id)
                  ->where('period_month', $this->selected_period_month);
            }])
            ->orderBy('name')
            ->paginate(15);

        // Stats for current active program
        $totalCollected = 0;
        $totalParticipatingEmployees = 0;
        if ($this->selected_program_id) {
            $totalCollected = (float) FlexibleDeduction::where('program_id', $this->selected_program_id)
                ->where('period_month', $this->selected_period_month)
                ->sum('amount');

            $totalParticipatingEmployees = FlexibleDeduction::where('program_id', $this->selected_program_id)
                ->where('period_month', $this->selected_period_month)
                ->where('amount', '>', 0)
                ->count();
        }

        // History programs
        $allProgramsHistory = FlexibleDeductionProgram::with('creator')
            ->withCount(['deductions as total_participants' => function ($q) {
                $q->where('amount', '>', 0);
            }])
            ->withSum('deductions as total_nominal', 'amount')
            ->orderBy('period_month', 'desc')
            ->paginate(10, ['*'], 'historyPage');

        return view('livewire.payroll.flexible-deduction-component', [
            'programs' => $programs,
            'currentProgram' => $currentProgram,
            'employees' => $employees,
            'divisions' => Division::orderBy('name')->get(),
            'totalCollected' => $totalCollected,
            'totalParticipatingEmployees' => $totalParticipatingEmployees,
            'allProgramsHistory' => $allProgramsHistory,
        ])->layout('layouts.app');
    }
}
