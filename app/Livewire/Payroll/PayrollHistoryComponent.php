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
    public $division = '';

    // Generate Form Properties
    public $generate_period_month;
    public $generate_start_date;
    public $generate_end_date;
    public $selectedPayrollId = null;
    public $selectedPayrollEmployeeName = '';
    public $isGenerating = false;

    public $selectedDeductions = [];
    public $selectedIncomes = [];

    #[\Livewire\Attributes\Computed]
    public function selectedDeductions()
    {
        if (!$this->selectedPayrollId) return [];
        $payroll = \App\Models\Payroll::with(['details'])->find($this->selectedPayrollId);
        return $payroll ? $payroll->details->where('type', 'deduction')->toArray() : [];
    }

    #[\Livewire\Attributes\Computed]
    public function selectedIncomes()
    {
        if (!$this->selectedPayrollId) return [];
        $payroll = \App\Models\Payroll::with(['details'])->find($this->selectedPayrollId);
        if (!$payroll) return [];

        $incomes = [];
        if ($payroll->basic_salary_earned > 0) {
            $incomes[] = [
                'name' => 'Gaji Pokok',
                'amount' => $payroll->basic_salary_earned
            ];
        }
        $earnings = $payroll->details->where('type', 'earning');
        foreach ($earnings as $earning) {
            $incomes[] = [
                'name' => $earning->name,
                'amount' => $earning->amount
            ];
        }
        return $incomes;
    }

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
                \Illuminate\Support\Facades\Log::warning('Payroll period parse warning: ' . $e->getMessage());
                $this->generate_start_date = date('Y-m-01');
                $this->generate_end_date = date('Y-m-t');
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

    public function updatingDivision()
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

        $throttleKey = 'payroll_generate_' . auth()->id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            session()->flash('flash.banner', "Proses generate payroll terlalu cepat. Harap tunggu {$seconds} detik.");
            session()->flash('flash.bannerStyle', 'danger');
            return;
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        $this->isGenerating = true;

        try {
            $generatedCount = 0;
            \Illuminate\Support\Facades\DB::transaction(function () use (&$generatedCount) {
                // Get active employees who have a salary setup
                $employees = \App\Models\User::where('group', 'user')->whereHas('salary')->with(['salary.savings'])->get();

                if ($employees->isEmpty()) {
                    throw new \Exception("Tidak ada karyawan aktif yang memiliki pengaturan gaji.");
                }

                $employeeIds = $employees->pluck('id')->toArray();

                // PERF-01: Eager-load all data in bulk queries to eliminate N+1 Query Cascade
                $allAttendances = \App\Models\Attendance::whereIn('user_id', $employeeIds)
                    ->whereBetween('date', [$this->generate_start_date, $this->generate_end_date])
                    ->with('shift')
                    ->get()
                    ->groupBy('user_id');

                $allOvertimes = \App\Models\Overtime::whereIn('employee_id', $employeeIds)
                    ->whereBetween('overtime_date', [$this->generate_start_date, $this->generate_end_date])
                    ->whereIn('status', ['approved', 'paid'])
                    ->get()
                    ->groupBy('employee_id');

                $allActiveLoans = \App\Models\Loan::whereIn('user_id', $employeeIds)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get()
                    ->groupBy('user_id');

                $allExistingPayrolls = Payroll::whereIn('employee_id', $employeeIds)
                    ->where('period_month', $this->generate_period_month)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('employee_id');

                // Build bulk schedule context for all employees in period
                $scheduleContext = \App\Services\AttendanceScheduleService::buildContext($employees, $this->generate_start_date, $this->generate_end_date);

                $allPayrollDetailsToInsert = [];

                foreach ($employees as $emp) {
                    $generatedCount++;
                    // Check if payroll already exists for this period (PERF-01 + BL-02)
                    $existing = $allExistingPayrolls->get($emp->id);
                    
                    if ($existing && $existing->status != 'draft') {
                        continue; // Skip if already approved or paid
                    }

                    if ($existing) {
                        \App\Models\LoanInstallment::where('payroll_id', $existing->id)->delete();
                        \App\Models\SavingTransaction::where('reference_type', 'payroll')->where('reference_id', $existing->id)->delete();
                        \App\Models\PayrollDetail::where('payroll_id', $existing->id)->delete();
                        $existing->delete();
                    }

                    $salary = $emp->salary;

                    // Fetch Attendances from batch Collection
                    $attendances = $allAttendances->get($emp->id, collect());

                    $total_paid_days = $attendances->whereIn('status', ['present', 'late', 'wfh', 'imp'])->count();
                    $total_present = $attendances->whereIn('status', ['present', 'late'])->count();
                    
                    // Dynamic Absent Calculation (using AttendanceScheduleService as single source of truth)
                    $start_period = \Carbon\Carbon::parse($this->generate_start_date);
                    $end_period = \Carbon\Carbon::parse($this->generate_end_date);
                    
                    $attendancesByDate = $attendances->groupBy(function($item) {
                        return $item->date->format('Y-m-d');
                    });
                    
                    $missing_absent_days = 0;
                    
                    // BL-04 Fix: Check previous consecutive leave leading up to $start_period across month boundary
                    $consecutive_cuti = 0;
                    $prev_check_date = $start_period->copy()->subDay();
                    while ($prev_check_date->gte($start_period->copy()->subDays(10))) {
                        $prev_att = \App\Models\Attendance::where('user_id', $emp->id)
                            ->where('date', $prev_check_date->format('Y-m-d'))
                            ->first();

                        if ($prev_att && $prev_att->status === 'leave') {
                            $consecutive_cuti++;
                            $prev_check_date->subDay();
                        } else {
                            break;
                        }
                    }

                    $penalized_cuti_days = 0;
                    $late_days_count = 0;
                    $actual_working_days = 0;

                    for ($d = $start_period->copy(); $d->lte($end_period); $d->addDay()) {
                        if ($scheduleContext->isWorkingDay($emp, $d)) {
                            $actual_working_days++;
                            $records = $attendancesByDate->get($d->format('Y-m-d'), collect());
                            $hasValidRecord = $records->whereNotIn('status', ['absent', 'dayoff'])->isNotEmpty();
                            $isExplicitDayOff = $records->where('status', 'dayoff')->isNotEmpty();

                            if (!$hasValidRecord && !$isExplicitDayOff) {
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

                    // Fetch Overtimes from batch Collection
                    $empOvertimes = $allOvertimes->get($emp->id, collect());
                    $total_overtime_hours = (float) $empOvertimes->sum('duration_hours');

                    // Allowances Calculation - BL-01 Fix: Explicit integer rounding
                    $basic_salary_earned = 0;
                    $meal_allowance = 0;
                    $transport_allowance = 0;
                    $attendance_allowance = 0;
                    
                    if ($salary->salary_type == 'daily') {
                        $basic_salary_earned = (int) round($salary->basic_salary * $total_paid_days);
                        $meal_allowance = (int) round($salary->meal_allowance * $total_paid_days);
                        $transport_allowance = (int) round($salary->transport_allowance * $total_paid_days);
                        $attendance_allowance = (int) round($salary->attendance_allowance);
                    } else { // monthly
                        $basic_salary_earned = (int) round($salary->basic_salary);
                        $meal_allowance = (int) round($salary->meal_allowance);
                        $transport_allowance = (int) round($salary->transport_allowance);
                        $attendance_allowance = (int) round($salary->attendance_allowance);
                    }

                    $total_allowance = (int) round($meal_allowance + $transport_allowance + $attendance_allowance);
                    $total_overtime_pay = 0; // Overtime pay is not added to net salary as per business rule

                    // Fixed Income for deductions reference
                    $fixed_income = (int) round($salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance);
                    $days_divisor = $salary->working_days_per_month ?? 25;
                    
                    if ($actual_working_days > 0 && $actual_working_days < $days_divisor) {
                        $days_divisor = $actual_working_days;
                    }
                    
                    $daily_rate_approx = $days_divisor > 0 ? (int) round($fixed_income / $days_divisor) : 0;

                    // Standard Deductions - BL-01 Fix: Explicit integer rounding
                    $late_deduction = (int) round($total_late_minutes * $salary->late_deduction_per_minute);
                    $imp_deduction = ($days_divisor > 0) ? (int) round($total_unreplaced_imp_minutes * ($fixed_income / ($days_divisor * 8 * 60))) : 0;
                    
                    // Advanced Deductions (Capped at days_divisor to prevent >100% deductions)
                    $effective_absent = min($total_absent, max(1, $days_divisor));
                    $absent_deduction = (int) round($daily_rate_approx * $effective_absent);
                    
                    $effective_excused = min($total_excused, max(1, $days_divisor));
                    $excused_deduction = ($days_divisor > 0) ? (int) round(($effective_excused / ($days_divisor * 2)) * $fixed_income + ($effective_excused / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;
                    
                    $effective_sick = min($total_sick, max(1, $days_divisor));
                    $sick_deduction = ($days_divisor > 0) ? (int) round(($effective_sick / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;
                    
                    $effective_cuti = min($penalized_cuti_days, max(1, $days_divisor));
                    $cuti_deduction = ($days_divisor > 0) ? (int) round(($effective_cuti / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;
                    
                    $effective_wfh = min($total_wfh, max(1, $days_divisor));
                    if ($emp->count_wfo) {
                        $wfh_deduction = 0;
                    } else {
                        $wfh_deduction = ($days_divisor > 0) ? (int) round(($effective_wfh / $days_divisor) * (0.5 * $fixed_income)) : 0;
                    }
                    $late_penalty_deduction = ($late_days_count > 3) ? (int) round(0.10 * $salary->attendance_allowance) : 0;

                    // BL-05 Fix: Daily Workers deduction clamping
                    if ($salary->salary_type == 'daily') {
                        $absent_deduction = 0;
                        $excused_deduction = 0;
                        $sick_deduction = 0;
                        $cuti_deduction = 0;
                        $wfh_deduction = 0;

                        // Daily workers: max late deduction capped at daily rate to prevent negative daily earnings
                        $daily_rate = (int) round($salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance);
                        $late_deduction = min($late_deduction, $daily_rate * max(1, $late_days_count));
                        $late_penalty_deduction = min($late_penalty_deduction, (int) round(0.10 * $salary->attendance_allowance));
                    }

                    // Syirkah / Savings Logic
                    $syirkah_deduction = 0;
                    $syirkah_mandatory = 0;
                    $syirkah_secondary = 0;
                    $savingProgram = null;

                    if ($salary->savings_id && $salary->savings && $total_paid_days >= 7) {
                        $savingProgram = $salary->savings;
                        $syirkah_mandatory = (int) round($savingProgram->mandatory_savings);
                        
                        // Use custom voluntary savings override if configured for this employee, otherwise fallback to master program default
                        if ($salary->custom_secondary_savings !== null) {
                            $syirkah_secondary = (int) round($salary->custom_secondary_savings);
                        } else {
                            $syirkah_secondary = (int) round($savingProgram->secondary_savings);
                        }
                        
                        $syirkah_deduction = $syirkah_mandatory + $syirkah_secondary;
                    }

                    // Loan / Kasbon Logic from batch Collection (PERF-01 + BL-02)
                    $active_loans = $allActiveLoans->get($emp->id, collect());
                    $loan_deduction = 0;
                    $loan_installments_to_save = [];
                    foreach ($active_loans as $loan) {
                        $installment = (int) round(min($loan->installment_amount, $loan->remaining_balance));
                        if ($installment > 0) {
                            $loan_deduction += $installment;
                            $loan_installments_to_save[] = [
                                'loan' => $loan,
                                'amount' => $installment,
                            ];
                        }
                    }

                    $total_gross = $basic_salary_earned + $total_allowance + $total_overtime_pay;
                    $total_deduction = (int) round($absent_deduction + $late_deduction + $imp_deduction + $excused_deduction + $sick_deduction + $cuti_deduction + $wfh_deduction + $late_penalty_deduction + $syirkah_deduction + $loan_deduction);
                    $total_deduction = min($total_deduction, $total_gross);
                    $net_salary = max(0, $total_gross - $total_deduction);

                    $total_unreplaced_imp_hours = $total_unreplaced_imp_minutes > 0 ? (float) ($total_unreplaced_imp_minutes / 60) : 0;
                    
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

                    // Save SavingTransaction via SavingTransactionService
                    if ($syirkah_deduction > 0 && $savingProgram) {
                        \App\Services\SavingTransactionService::recordPayrollSyirkah(
                            $emp->id,
                            $savingProgram->id,
                            $syirkah_mandatory,
                            $syirkah_secondary,
                            $this->generate_period_month,
                            $payroll->id
                        );
                    }

                    // Save LoanInstallments
                    foreach ($loan_installments_to_save as $item) {
                        \App\Models\LoanInstallment::create([
                            'loan_id' => $item['loan']->id,
                            'amount_paid' => $item['amount'],
                            'payment_method' => 'payroll_deduction',
                            'payroll_id' => $payroll->id,
                            'status' => 'pending',
                        ]);
                        $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => 'Cicilan Pinjaman', 'amount' => $item['amount'], 'created_at' => now(), 'updated_at' => now()];
                    }

                    // Save Payroll Details (Rincian)
                    $nowTs = now();
                    if ($meal_allowance > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => 'Uang Makan', 'amount' => $meal_allowance, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($transport_allowance > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => 'Uang Transport', 'amount' => $transport_allowance, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($attendance_allowance > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => 'Tunjangan Absensi', 'amount' => $attendance_allowance, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    
                    // Compact Overtime Display (BL-03 Fix)
                    if ($total_overtime_hours > 0) {
                        $h = floor($total_overtime_hours);
                        $m = round(($total_overtime_hours - $h) * 60);
                        $compactOvertimeStr = $m > 0 ? "{$h}j {$m}m" : "{$h}j";
                        $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'earning', 'name' => "Lembur ({$compactOvertimeStr})", 'amount' => 0, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    }

                    if ($absent_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Alpa ($total_absent Hari)", 'amount' => $absent_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($late_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Terlambat ($total_late_minutes Menit)", 'amount' => $late_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($excused_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Izin ($total_excused Hari)", 'amount' => $excused_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($sick_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Sakit ($total_sick Hari)", 'amount' => $sick_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($cuti_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Cuti >2 Hr ($penalized_cuti_days Hari)", 'amount' => $cuti_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($wfh_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan WFH ($total_wfh Hari)", 'amount' => $wfh_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($late_penalty_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Penalti Terlambat >3x", 'amount' => $late_penalty_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($imp_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan IMP Tdk Diganti ($total_unreplaced_imp_minutes Mnt)", 'amount' => $imp_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    if ($syirkah_deduction > 0) $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => "Potongan Syirkah", 'amount' => $syirkah_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                }

                // Batch insert all PayrollDetails in chunks for maximum performance
                if (!empty($allPayrollDetailsToInsert)) {
                    foreach (array_chunk($allPayrollDetailsToInsert, 500) as $chunk) {
                        \App\Models\PayrollDetail::insert($chunk);
                    }
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

    public bool $showDeductionModal = false;
    public bool $showIncomeModal = false;

    public function showDeductions($payrollId)
    {
        $payroll = \App\Models\Payroll::with('employee')->find($payrollId);
        if ($payroll) {
            $this->selectedPayrollId = $payrollId;
            $this->selectedPayrollEmployeeName = $payroll->employee->name ?? 'Karyawan';
            $this->showDeductionModal = true;
        }
    }

    public function closeDeductionModal()
    {
        $this->showDeductionModal = false;
        $this->selectedPayrollId = null;
    }

    public function showIncomes($payrollId)
    {
        $payroll = \App\Models\Payroll::with('employee')->find($payrollId);
        if ($payroll) {
            $this->selectedPayrollId = $payrollId;
            $this->selectedPayrollEmployeeName = $payroll->employee->name ?? 'Karyawan';
            $this->showIncomeModal = true;
        }
    }

    public function closeIncomeModal()
    {
        $this->showIncomeModal = false;
        $this->selectedPayrollId = null;
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

        if ($this->division) {
            $query->whereHas('employee', function ($q) {
                $q->where('division_id', $this->division);
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
