<?php

namespace App\Livewire\Payroll;

use App\Models\Payroll;
use Livewire\Component;
use Livewire\WithPagination;
use Laravel\Jetstream\InteractsWithBanner;

class PayrollHistoryComponent extends Component
{
    use WithPagination, InteractsWithBanner;

    public $month = '';
    public $search = '';
    public $status = '';

    // Generate Form Properties
    public $generate_period_month;
    public $generate_start_date;
    public $generate_end_date;
    public $showDeductionModal = false;
    public $showIncomeModal = false;
    public $selectedDeductions = [];
    public $selectedIncomes = [];
    public $selectedPayrollEmployeeName = '';
    public $isGenerating = false;

    protected $queryString = [];

    public function mount()
    {
        $this->generate_period_month = date('Y-m');
        $this->generate_start_date = date('Y-m-01');
        $this->generate_end_date = date('Y-m-t');
    }

    public function updatedGeneratePeriodMonth($value)
    {
        if ($value) {
            try {
                $this->generate_start_date = \Carbon\Carbon::parse($value . '-01')->format('Y-m-d');
                $this->generate_end_date = \Carbon\Carbon::parse($value . '-01')->endOfMonth()->format('Y-m-d');
            } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Payroll Generation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                // Ignore parsing errors
            }
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingMonth()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public $isDeleteModalOpen = false;
    public $payrollIdToDelete = null;

    public $isGenerateModalOpen = false;

    #[\Livewire\Attributes\On('open-generate-modal')]
    public function openGenerateModal()
    {
        $this->isGenerateModalOpen = true;
    }

    public function closeGenerateModal()
    {
        $this->isGenerateModalOpen = false;
    }

    public function markAsPaid($id)
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);
        
        $payroll = Payroll::findOrFail($id);
        $payroll->update([
            'status' => 'paid',
            'payment_date' => now(),
        ]);
        
        $this->banner('Status gaji berhasil diubah menjadi Paid (Telah Dibayar).');
    }
    
    public function confirmDelete($id)
    {
        $this->payrollIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function cancelDelete()
    {
        $this->isDeleteModalOpen = false;
        $this->payrollIdToDelete = null;
    }

    public function deletePayroll()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);
        
        if ($this->payrollIdToDelete) {
            $payroll = Payroll::findOrFail($this->payrollIdToDelete);
            $payroll->delete();
            $this->banner('Data gaji berhasil dihapus.');
        }
        
        $this->isDeleteModalOpen = false;
        $this->payrollIdToDelete = null;
    }
    
    #[\Livewire\Attributes\On('mark-all-as-paid')]
    public function markAllAsPaid()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);
        
        $query = Payroll::where('status', 'draft');
        
        if ($this->month) {
            $query->where('period_month', $this->month);
        }
        
        $count = $query->count();
        
        if ($count > 0) {
            $query->update([
                'status' => 'paid',
                'payment_date' => now(),
            ]);
            $this->banner("$count data gaji berhasil ditandai sebagai Paid.");
        } else {
            session()->flash('flash.banner', 'Tidak ada data draft untuk bulan ini.');
            session()->flash('flash.bannerStyle', 'danger');
        }
    }

    public function generatePayroll()
    {
        $this->validate([
            'generate_period_month' => 'required|date_format:Y-m',
            'generate_start_date' => 'required|date',
            'generate_end_date' => 'required|date|after_or_equal:generate_start_date',
        ]);
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);

        $this->isGenerating = true;

        try {
            $generatedCount = 0;
            \Illuminate\Support\Facades\DB::transaction(function () use (&$generatedCount) {
                // Get active employees who have a salary setup
                $employees = \App\Models\User::where('group', 'user')->whereHas('salary')->with(['salary.savings'])->get();

                if ($employees->isEmpty()) {
                    throw new \Exception("Tidak ada karyawan aktif yang memiliki pengaturan gaji.");
                }

                foreach ($employees as $emp) {
                    $generatedCount++;
                    // Check if payroll already exists for this period, delete it (overwrite draft)
                    $existing = Payroll::where('employee_id', $emp->id)
                        ->where('period_month', $this->generate_period_month)
                        ->first();
                    
                    if ($existing && $existing->status != 'draft') {
                        continue; // Skip if already approved or paid
                    }

                    if ($existing) {
                        $existing->delete();
                    }

                    $salary = $emp->salary;

                    // Fetch Attendances
                    $attendances = \App\Models\Attendance::where('user_id', $emp->id)
                        ->whereBetween('date', [$this->generate_start_date, $this->generate_end_date])
                        ->with('shift')
                        ->get();

                    $total_paid_days = $attendances->whereIn('status', ['present', 'late', 'wfh', 'imp'])->count();
                    $total_present = $attendances->whereIn('status', ['present', 'late'])->count();
                    
                    // Dynamic Absent Calculation (including missing records, skipping Sundays, up to today)
                    $start_period = \Carbon\Carbon::parse($this->generate_start_date);
                    $end_period = \Carbon\Carbon::parse($this->generate_end_date);
                    $today_date = \Carbon\Carbon::today();
                    
                    $attendancesByDate = $attendances->groupBy(function($item) {
                        return $item->date->format('Y-m-d');
                    });
                    
                    $missing_absent_days = 0;
                    $consecutive_cuti = 0;
                    $penalized_cuti_days = 0;
                    $late_days_count = 0;
                    $actual_working_days = 0;

                    for ($d = $start_period->copy(); $d->lte($end_period); $d->addDay()) {
                        if (!$d->isSunday()) {
                            $actual_working_days++;
                            $records = $attendancesByDate->get($d->format('Y-m-d'), collect());
                            $hasValidRecord = $records->where('status', '!=', 'absent')->isNotEmpty();
                            if (!$hasValidRecord) {
                                $missing_absent_days++;
                            }
                            
                            // Check Cuti Beruntun
                            $is_cuti = $records->where('status', 'leave')->isNotEmpty();
                            if ($is_cuti) {
                                $consecutive_cuti++;
                                if ($consecutive_cuti > 2) {
                                    $penalized_cuti_days++;
                                }
                            } else {
                                $consecutive_cuti = 0;
                            }
                            
                            // Check Late Frequency
                            if ($records->where('status', 'late')->isNotEmpty()) {
                                $late_days_count++;
                            }
                        }
                    }
                    $total_absent = $missing_absent_days;
                    $total_excused = $attendances->where('status', 'excused')->count();
                    $total_sick = $attendances->where('status', 'sick')->count();
                    $total_wfh = $attendances->where('status', 'wfh')->count();
                    
                    $total_late_minutes = 0;
                    foreach ($attendances->where('status', 'late') as $att) {
                        if ($att->shift && $att->time_in) {
                            $timeIn = \Carbon\Carbon::parse($att->time_in);
                            $shiftStart = \Carbon\Carbon::parse($att->shift->start_time);
                            if ($timeIn->greaterThan($shiftStart)) {
                                $total_late_minutes += $timeIn->diffInMinutes($shiftStart);
                            }
                        }
                    }

                    // Calculate Unreplaced IMP
                    $total_unreplaced_imp_minutes = 0;
                    foreach ($attendances->where('status', 'imp') as $att) {
                        $imp_duration = $att->imp_duration_minutes ?? 0;
                        $replaced = $att->replaced_duration_minutes ?? 0;
                        $unreplaced = max(0, $imp_duration - $replaced);
                        $total_unreplaced_imp_minutes += $unreplaced;
                    }

                    // Fetch Overtimes
                    $total_overtime_hours = \App\Models\Overtime::where('employee_id', $emp->id)
                        ->whereBetween('overtime_date', [$this->generate_start_date, $this->generate_end_date])
                        ->where('status', 'approved')
                        ->sum('duration_hours');

                    // Allowances Calculation
                    $basic_salary_earned = 0;
                    $meal_allowance = 0;
                    $transport_allowance = 0;
                    $attendance_allowance = 0;
                    
                    // Daily vs Monthly Logic
                    if ($salary->salary_type == 'daily') {
                        $basic_salary_earned = $salary->basic_salary * $total_paid_days;
                        $meal_allowance = $salary->meal_allowance * $total_paid_days;
                        $transport_allowance = $salary->transport_allowance * $total_paid_days;
                        $attendance_allowance = $salary->attendance_allowance;
                    } else { // monthly
                        $basic_salary_earned = $salary->basic_salary;
                        $meal_allowance = $salary->meal_allowance;
                        $transport_allowance = $salary->transport_allowance;
                        $attendance_allowance = $salary->attendance_allowance;
                    }

                    $total_allowance = $meal_allowance + $transport_allowance + $attendance_allowance;
                    $total_overtime_pay = $total_overtime_hours * $salary->overtime_rate_per_hour;

                    // Fixed Income for deductions reference
                    $fixed_income = $salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance;
                    $days_divisor = $salary->working_days_per_month ?? 25;
                    
                    if ($actual_working_days > 0 && $actual_working_days < $days_divisor) {
                        $days_divisor = $actual_working_days;
                    }
                    
                    $daily_rate_approx = $days_divisor > 0 ? $fixed_income / $days_divisor : 0;

                    // Standard Deductions
                    $late_deduction = $total_late_minutes * $salary->late_deduction_per_minute;
                    // IMP deduction based on exactly the minute proportion of total salary (user formula)
                    
                    $imp_deduction = ($days_divisor > 0) ? $total_unreplaced_imp_minutes * ($fixed_income / ($days_divisor * 8 * 60)) : 0;
                    
                    // Advanced Deductions (Capped at days_divisor to prevent >100% deductions causing negative salary)
                    $effective_absent = min($total_absent, max(1, $days_divisor));
                    $absent_deduction = $daily_rate_approx * $effective_absent;
                    
                    $effective_excused = min($total_excused, max(1, $days_divisor));
                    $excused_deduction = ($days_divisor > 0) ? ($effective_excused / ($days_divisor * 2)) * $fixed_income + ($effective_excused / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance) : 0;
                    
                    $effective_sick = min($total_sick, max(1, $days_divisor));
                    $sick_deduction = ($days_divisor > 0) ? ($effective_sick / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance) : 0;
                    
                    $effective_cuti = min($penalized_cuti_days, max(1, $days_divisor));
                    $cuti_deduction = ($days_divisor > 0) ? ($effective_cuti / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance) : 0;
                    
                    $effective_wfh = min($total_wfh, max(1, $days_divisor));
                    if ($emp->count_wfo) {
                        $wfh_deduction = 0;
                    } else {
                        $wfh_deduction = ($days_divisor > 0) ? ($effective_wfh / $days_divisor) * (0.5 * $fixed_income) : 0;
                    }
                    $late_penalty_deduction = ($late_days_count > 3) ? (0.10 * $salary->attendance_allowance) : 0;

                    // If daily worker, they already lose income by not being present, so they shouldn't get double deducted for Alpa/Sakit/Izin/Cuti
                    // BUT per the formula request, if we strictly apply it, it might deduct them twice.
                    // Assuming formulas apply universally:
                    if ($salary->salary_type == 'daily') {
                        $absent_deduction = 0; // Already zeroed out from basic_salary_earned
                        $excused_deduction = 0; // Already zeroed out from basic_salary_earned
                        $sick_deduction = 0; // Already zeroed out from basic_salary_earned
                        $cuti_deduction = 0; // Already zeroed out from basic_salary_earned
                        $wfh_deduction = 0; // Already zeroed out from basic_salary_earned
                        // The UI & manual input expects this mostly for monthly. 
                        // If you want it applied to daily, you can adjust this later.
                    }

                    // Syirkah / Savings Logic
                    $syirkah_deduction = 0;
                    $period_date = \Carbon\Carbon::parse($this->generate_period_month . '-01');
                    
                    // Cleanup existing history for this generated month
                    \App\Models\SavingsHistory::where('user_id', $emp->id)
                        ->whereYear('created_at', $period_date->year)
                        ->whereMonth('created_at', $period_date->month)
                        ->delete();

                    if ($salary->savings_id && $salary->savings && $total_paid_days >= 7) {
                        $savingProgram = $salary->savings;
                        $syirkah_mandatory = $savingProgram->mandatory_savings;
                        $syirkah_secondary = $savingProgram->secondary_savings;
                        $syirkah_deduction = $syirkah_mandatory + $syirkah_secondary;
                        
                        if ($syirkah_deduction > 0) {
                            $prev_history = \App\Models\SavingsHistory::where('user_id', $emp->id)
                                ->where('created_at', '<', $period_date->copy()->startOfMonth())
                                ->orderBy('created_at', 'desc')
                                ->first();
                                
                            $prev_mandatory = $prev_history ? $prev_history->total_mandatory : 0;
                            $prev_secondary = $prev_history ? $prev_history->total_secondary : 0;
                            
                            $new_total_mandatory = $prev_mandatory + $syirkah_mandatory;
                            $new_total_secondary = $prev_secondary + $syirkah_secondary;
                            $new_total_savings = $new_total_mandatory + $new_total_secondary;
                            
                            \App\Models\SavingsHistory::create([
                                'user_id' => $emp->id,
                                'savings_id' => $savingProgram->id,
                                'mandatory_savings' => $syirkah_mandatory,
                                'secondary_savings' => $syirkah_secondary,
                                'total_mandatory' => $new_total_mandatory,
                                'total_secondary' => $new_total_secondary,
                                'total_savings' => $new_total_savings,
                                'created_at' => $period_date->copy()->endOfMonth(),
                                'updated_at' => $period_date->copy()->endOfMonth(),
                            ]);
                        }
                    }

                    $total_deduction = $absent_deduction + $late_deduction + $imp_deduction + $excused_deduction + $sick_deduction + $cuti_deduction + $wfh_deduction + $late_penalty_deduction + $syirkah_deduction;

                    $net_salary = $basic_salary_earned + $total_allowance + $total_overtime_pay - $total_deduction;

                    $total_unreplaced_imp_hours = $total_unreplaced_imp_minutes > 0 ? $total_unreplaced_imp_minutes / 60 : 0;
                    
                    // Save Payroll
                    $payroll = Payroll::create([
                        'employee_id' => $emp->id,
                        'period_month' => $this->generate_period_month,
                        'start_date' => $this->generate_start_date,
                        'end_date' => $this->generate_end_date,
                        'basic_salary_earned' => $basic_salary_earned,
                        'total_allowance' => $total_allowance,
                        'total_overtime_pay' => $total_overtime_pay,
                        'total_deduction' => $total_deduction,
                        'net_salary' => $net_salary,
                        'total_present' => $total_present,
                        'total_wfh' => $total_wfh,
                        'total_absent' => $total_absent,
                        'total_sick' => $total_sick,
                        'total_excused' => $total_excused,
                        'penalized_cuti_days' => $penalized_cuti_days,
                        'late_days_count' => $late_days_count,
                        'total_late_minutes' => $total_late_minutes,
                        'total_overtime_hours' => $total_overtime_hours,
                        'total_unreplaced_imp_hours' => $total_unreplaced_imp_hours,
                        'status' => 'draft',
                        'payment_date' => null,
                        'notes' => 'Generated automatically.',
                    ]);

                    // Save Payroll Details (Rincian)
                    if ($meal_allowance > 0) \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => 'Uang Makan', 'amount' => $meal_allowance]);
                    if ($transport_allowance > 0) \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => 'Uang Transport', 'amount' => $transport_allowance]);
                    if ($attendance_allowance > 0) \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => 'Tunjangan Absensi', 'amount' => $attendance_allowance]);
                    if ($total_overtime_pay > 0) \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => "Lembur ($total_overtime_hours Jam)", 'amount' => $total_overtime_pay]);

                    if ($absent_deduction > 0) {
                        $rate_label = ' @ Rp ' . number_format($daily_rate_approx, 0, ',', '.');
                        \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Mangkir/Absen ($total_absent Hari{$rate_label})", 'amount' => $absent_deduction]);
                    }
                    if ($excused_deduction > 0) {
                        \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Izin ($total_excused Hari)", 'amount' => $excused_deduction]);
                    }
                    if ($sick_deduction > 0) {
                        \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Sakit ($total_sick Hari)", 'amount' => $sick_deduction]);
                    }
                    if ($cuti_deduction > 0) {
                        \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Cuti Beruntun ($penalized_cuti_days Hari)", 'amount' => $cuti_deduction]);
                    }
                    if ($wfh_deduction > 0) {
                        \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan WFH/WFA ($total_wfh Hari)", 'amount' => $wfh_deduction]);
                    }
                    if ($late_penalty_deduction > 0) {
                        \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Penalti Sering Terlambat ($late_days_count Kali)", 'amount' => $late_penalty_deduction]);
                    }
                    if ($late_deduction > 0) \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Terlambat ($total_late_minutes Menit)", 'amount' => $late_deduction]);
                    if ($imp_deduction > 0) \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan IMP Tdk Diganti ($total_unreplaced_imp_hours Jam)", 'amount' => $imp_deduction]);
                    if ($syirkah_deduction > 0) \App\Models\PayrollDetail::create(['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Syirkah", 'amount' => $syirkah_deduction]);
                }
            });

            $this->banner("$generatedCount Payroll berhasil digenerate untuk bulan " . $this->generate_period_month);
            // reset filter to see generated month
            $this->month = $this->generate_period_month;
            $this->resetPage();
            $this->closeGenerateModal();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Payroll Generation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            session()->flash('flash.banner', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            session()->flash('flash.bannerStyle', 'danger');
        }

        $this->isGenerating = false;
    }

    public function showDeductions($payrollId)
    {
        $payroll = \App\Models\Payroll::with(['details', 'employee'])->find($payrollId);
        if ($payroll) {
            $this->selectedDeductions = $payroll->details->where('type', 'deduction')->toArray();
            $this->selectedPayrollEmployeeName = $payroll->employee->name ?? 'Karyawan';
            $this->showDeductionModal = true;
        }
    }

    public function closeDeductionModal()
    {
        $this->showDeductionModal = false;
        // Data selectedDeductions dan selectedPayrollEmployeeName JANGAN di-clear di sini.
        // Hal ini untuk menjaga state DOM tetap terisi saat animasi transisi (fade out) penutupan modal berjalan.
        // Data tersebut akan otomatis tertimpa (overwrite) ketika user membuka modal untuk payroll yang lain.
    }

    public function showIncomes($payrollId)
    {
        $payroll = \App\Models\Payroll::with(['details', 'employee'])->find($payrollId);
        if ($payroll) {
            $incomes = [];
            
            // Tambahkan Gaji Pokok dari master/tabel payroll
            if ($payroll->basic_salary_earned > 0) {
                $incomes[] = [
                    'name' => 'Gaji Pokok',
                    'amount' => $payroll->basic_salary_earned
                ];
            }

            // Tambahkan Tunjangan dan Lembur dari detail (earning)
            $earnings = $payroll->details->where('type', 'earning');
            foreach ($earnings as $earning) {
                $incomes[] = [
                    'name' => $earning->name,
                    'amount' => $earning->amount
                ];
            }

            $this->selectedIncomes = $incomes;
            $this->selectedPayrollEmployeeName = $payroll->employee->name ?? 'Karyawan';
            $this->showIncomeModal = true;
        }
    }

    public function closeIncomeModal()
    {
        $this->showIncomeModal = false;
    }

    public function render()
    {
        abort_unless(auth()->user()->isPayroll || auth()->user()->isSuperadmin, 403);

        $query = Payroll::with('employee')->orderBy('created_at', 'desc');

        if ($this->month) {
            $query->where('period_month', $this->month);
        }

        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $payrolls = $query->paginate(15);

        return view('livewire.payroll.payroll-history-component', [
            'payrolls' => $payrolls,
        ])->layout('layouts.app');
    }
}
