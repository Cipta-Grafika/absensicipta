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
                $employees = \App\Models\User::where('group', '!=', 'superadmin')
                    ->where(function ($q) {
                        $q->whereNull('status')
                          ->orWhereNotIn('status', ['fired', 'resign']);
                    })
                    ->whereHas('salary')
                    ->with(['salary.savings'])
                    ->get();

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

                $allFlexibleDeductions = \App\Models\FlexibleDeduction::whereIn('user_id', $employeeIds)
                    ->where('period_month', $this->generate_period_month)
                    ->where('amount', '>', 0)
                    ->with('program')
                    ->get()
                    ->groupBy('user_id');

                // Clean up any existing payroll and related records for the regenerated month to ensure full overwrite and no duplicates
                $allExistingPayrolls = Payroll::whereIn('employee_id', $employeeIds)
                    ->where('period_month', $this->generate_period_month)
                    ->lockForUpdate()
                    ->get();

                $existingPayrollIds = $allExistingPayrolls->pluck('id');
                if ($existingPayrollIds->isNotEmpty()) {
                    \App\Models\LoanInstallment::whereIn('payroll_id', $existingPayrollIds)->delete();
                    \App\Models\SavingTransaction::where('reference_type', 'payroll')->whereIn('reference_id', $existingPayrollIds)->delete();
                    
                    $existingFlexIds = \App\Models\FlexibleDeduction::whereIn('payroll_id', $existingPayrollIds)->pluck('id');
                    if ($existingFlexIds->isNotEmpty()) {
                        \App\Models\SavingTransaction::where('reference_type', 'flexible_deduction')->whereIn('reference_id', $existingFlexIds)->delete();
                        \App\Models\FlexibleDeduction::whereIn('id', $existingFlexIds)->update(['is_applied' => false, 'payroll_id' => null, 'saving_transaction_id' => null]);
                    }

                    \App\Models\PayrollDetail::whereIn('payroll_id', $existingPayrollIds)->delete();
                    Payroll::whereIn('id', $existingPayrollIds)->delete();

                    foreach ($employeeIds as $empId) {
                        \App\Services\SavingTransactionService::recalculateUserTransactions($empId);
                    }
                }

                // Build bulk schedule context for all employees in period
                $scheduleContext = \App\Services\AttendanceScheduleService::buildContext($employees, $this->generate_start_date, $this->generate_end_date);

                $allPayrollDetailsToInsert = [];

                foreach ($employees as $emp) {
                    $generatedCount++;

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

                    // Fixed Income for deductions reference (standard 25 working days base)
                    $fixed_income = (int) round($salary->basic_salary + $salary->meal_allowance + $salary->transport_allowance + $salary->attendance_allowance);
                    $days_divisor = $salary->working_days_per_month ?: 25;
                    $daily_rate_approx = (float) ($fixed_income / $days_divisor);

                    // Standard Deductions - BL-01 Fix: Explicit integer rounding
                    $late_deduction = (int) round($total_late_minutes * $salary->late_deduction_per_minute);
                    $imp_deduction = ($days_divisor > 0) ? (int) round($total_unreplaced_imp_minutes * ($fixed_income / ($days_divisor * 8 * 60))) : 0;

                    // Absent Deduction Rule:
                    // If employee has ZERO attendance/paid days ($total_paid_days == 0): Full salary deduction (100% deduction, Net = 0)
                    if ($total_paid_days == 0) {
                        $absent_deduction = $fixed_income;
                    } elseif ($total_absent > 0) {
                        if ($total_paid_days >= $days_divisor) {
                            // Attended full 25+ days, but has some Alfa days in the month
                            // Deduct exactly the number of Alfa days
                            $effective_absent = min($total_absent, $days_divisor);
                            $absent_deduction = (int) round($daily_rate_approx * $effective_absent);
                        } else {
                            // Attended less than 25 days:
                            // Guaranteed to earn paid days ($total_paid_days * $daily_rate_approx)
                            // Deduct unpaid days out of 25 working days
                            $unpaid_working_days = max(1, $days_divisor - $total_paid_days);
                            $absent_deduction = (int) round($daily_rate_approx * $unpaid_working_days);
                        }
                    } else {
                        $absent_deduction = 0;
                    }

                    $effective_excused = min($total_excused, $days_divisor);
                    $excused_deduction = ($days_divisor > 0) ? (int) round(($effective_excused / ($days_divisor * 2)) * $fixed_income + ($effective_excused / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;

                    $effective_sick = min($total_sick, $days_divisor);
                    $sick_deduction = ($days_divisor > 0) ? (int) round(($effective_sick / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;

                    $effective_cuti = min($penalized_cuti_days, $days_divisor);
                    $cuti_deduction = ($days_divisor > 0) ? (int) round(($effective_cuti / $days_divisor) * ($salary->transport_allowance + $salary->attendance_allowance)) : 0;

                    $effective_wfh = min($total_wfh, $days_divisor);
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

                    // Flexible Deductions Logic (Galang Dana / Kustom)
                    $emp_flex_deductions = $allFlexibleDeductions->get($emp->id, collect());
                    $flex_deduction_total = 0;
                    foreach ($emp_flex_deductions as $flex) {
                        if ($flex->deduction_source === 'payroll') {
                            $flex_deduction_total += (int) round($flex->amount);
                        }
                    }

                    // If zero attendance, ensure absent deduction + other deductions match exact total gross without exceeding
                    if ($total_paid_days == 0) {
                        $other_deductions = $syirkah_deduction + $loan_deduction + $flex_deduction_total;
                        $absent_deduction = max(0, $fixed_income - $other_deductions);
                    }

                    $total_gross = $basic_salary_earned + $total_allowance + $total_overtime_pay;
                    $total_deduction = (int) round($absent_deduction + $late_deduction + $imp_deduction + $excused_deduction + $sick_deduction + $cuti_deduction + $wfh_deduction + $late_penalty_deduction + $syirkah_deduction + $loan_deduction + $flex_deduction_total);
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

                    // Save Flexible Deductions to Payroll Details & Update Status
                    foreach ($emp_flex_deductions as $flex) {
                        $savingTx = null;
                        if ($flex->deduction_source !== 'payroll' && $savingProgram) {
                            $mandAmount = 0;
                            $secAmount = 0;
                            if ($flex->deduction_source === 'syirkah_mandatory') {
                                $mandAmount = $flex->amount;
                            } elseif ($flex->deduction_source === 'syirkah_secondary') {
                                $secAmount = $flex->amount;
                            } elseif ($flex->deduction_source === 'syirkah_all') {
                                $depSec = (float) \App\Models\SavingTransaction::where('user_id', $emp->id)->where('status', 'approved')->where('transaction_type', 'deposit')->sum('secondary_amount');
                                $wdSec = (float) \App\Models\SavingTransaction::where('user_id', $emp->id)->where('status', 'approved')->where('transaction_type', 'withdrawal')->sum('secondary_amount');
                                $availSec = max(0.0, $depSec - $wdSec);
                                $secAmount = min($flex->amount, $availSec);
                                $mandAmount = max(0.0, $flex->amount - $secAmount);
                            }

                            $savingTx = \App\Models\SavingTransaction::create([
                                'user_id' => $emp->id,
                                'savings_id' => $savingProgram->id,
                                'transaction_type' => 'withdrawal',
                                'mandatory_amount' => $mandAmount,
                                'secondary_amount' => $secAmount,
                                'status' => 'approved',
                                'period_month' => $this->generate_period_month,
                                'reference_type' => 'flexible_deduction',
                                'reference_id' => $flex->id,
                                'description' => 'Potongan ' . ($flex->program->name ?? 'Fleksibel') . ' via ' . $flex->deduction_source_label,
                                'approved_by' => \Illuminate\Support\Facades\Auth::id(),
                                'approved_at' => now(),
                            ]);
                            \App\Services\SavingTransactionService::recalculateUserTransactions($emp->id);
                        }

                        $flex->update([
                            'is_applied' => true,
                            'payroll_id' => $payroll->id,
                            'saving_transaction_id' => $savingTx?->id,
                        ]);

                        if ($flex->deduction_source === 'payroll') {
                            $allPayrollDetailsToInsert[] = [
                                'payroll_id' => $payroll->id,
                                'type' => 'deduction',
                                'name' => 'Potongan: ' . ($flex->program->name ?? 'Fleksibel'),
                                'amount' => (int) round($flex->amount),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
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

                    if ($absent_deduction > 0) {
                        $absentLabel = ($total_paid_days == 0)
                            ? "Potongan Alpa (Full $total_absent Hari)"
                            : "Potongan Alpa ($total_absent Hari)";
                        $allPayrollDetailsToInsert[] = ['payroll_id' => $payroll->id, 'type' => 'deduction', 'name' => $absentLabel, 'amount' => $absent_deduction, 'created_at' => $nowTs, 'updated_at' => $nowTs];
                    }
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
                        $preparedChunk = array_map(function ($row) {
                            if (!isset($row['id'])) {
                                $row['id'] = (string) \Illuminate\Support\Str::ulid();
                            }
                            return $row;
                        }, $chunk);
                        \App\Models\PayrollDetail::insert($preparedChunk);
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
        $payroll = \App\Models\Payroll::with(['employee', 'details'])->find($payrollId);
        if ($payroll) {
            $this->selectedPayrollId = $payrollId;
            $this->selectedPayrollEmployeeName = $payroll->employee->name ?? 'Karyawan';
            
            $deductions = [];
            if ($payroll->details->isNotEmpty()) {
                foreach ($payroll->details->where('type', 'deduction') as $d) {
                    $deductions[] = [
                        'name' => $d->name,
                        'amount' => (float) $d->amount,
                    ];
                }
            }

            // Fallback for older/migrated payroll records if details table was empty
            if (empty($deductions) && $payroll->total_deduction > 0) {
                // Check if there are Syirkah transactions for this payroll
                $syirkahTx = \App\Models\SavingTransaction::where('reference_type', 'payroll')->where('reference_id', $payroll->id)->first();
                if ($syirkahTx) {
                    $deductions[] = [
                        'name' => 'Potongan Syirkah',
                        'amount' => (float) ($syirkahTx->mandatory_amount + $syirkahTx->secondary_amount),
                    ];
                }

                // Check loans
                $loanInst = \App\Models\LoanInstallment::where('payroll_id', $payroll->id)->get();
                foreach ($loanInst as $li) {
                    $deductions[] = [
                        'name' => 'Cicilan Pinjaman',
                        'amount' => (float) $li->amount_paid,
                    ];
                }

                // Check flexible deductions
                $flexDeds = \App\Models\FlexibleDeduction::where('payroll_id', $payroll->id)->with('program')->get();
                foreach ($flexDeds as $fd) {
                    if ($fd->deduction_source === 'payroll') {
                        $deductions[] = [
                            'name' => 'Potongan: ' . ($fd->program->name ?? 'Fleksibel'),
                            'amount' => (float) $fd->amount,
                        ];
                    }
                }

                // Remaining deduction is attendance deductions (Alpa, Terlambat, Sakit, Izin, etc.)
                $currentSum = array_sum(array_column($deductions, 'amount'));
                $remaining = max(0, $payroll->total_deduction - $currentSum);
                if ($remaining > 0) {
                    $descParts = [];
                    if ($payroll->total_absent > 0) $descParts[] = "Alpa {$payroll->total_absent} hari";
                    if ($payroll->total_late_minutes > 0) $descParts[] = "Terlambat {$payroll->total_late_minutes} mnt";
                    if ($payroll->total_sick > 0) $descParts[] = "Sakit {$payroll->total_sick} hari";
                    if ($payroll->total_excused > 0) $descParts[] = "Izin {$payroll->total_excused} hari";
                    
                    $label = !empty($descParts) ? 'Potongan Kehadiran (' . implode(', ', $descParts) . ')' : 'Potongan Kehadiran & Lainnya';
                    $deductions[] = [
                        'name' => $label,
                        'amount' => (float) $remaining,
                    ];
                }
            }

            $this->selectedDeductions = $deductions;
            $this->showDeductionModal = true;
        }
    }

    public function closeDeductionModal()
    {
        $this->showDeductionModal = false;
        $this->selectedPayrollId = null;
        $this->selectedDeductions = [];
    }

    public function showIncomes($payrollId)
    {
        $payroll = \App\Models\Payroll::with(['employee', 'details'])->find($payrollId);
        if ($payroll) {
            $this->selectedPayrollId = $payrollId;
            $this->selectedPayrollEmployeeName = $payroll->employee->name ?? 'Karyawan';
            
            $incomes = [];
            if ($payroll->basic_salary_earned > 0) {
                $incomes[] = [
                    'name' => 'Gaji Pokok',
                    'amount' => (float) $payroll->basic_salary_earned,
                ];
            }

            if ($payroll->details->isNotEmpty()) {
                foreach ($payroll->details->where('type', 'earning') as $d) {
                    $incomes[] = [
                        'name' => $d->name,
                        'amount' => (float) $d->amount,
                    ];
                }
            }

            if (empty($incomes) || (count($incomes) === 1 && $payroll->total_allowance > 0)) {
                if ($payroll->total_allowance > 0 && !collect($incomes)->contains('name', 'Total Tunjangan')) {
                    $incomes[] = [
                        'name' => 'Total Tunjangan',
                        'amount' => (float) $payroll->total_allowance,
                    ];
                }
                if ($payroll->total_overtime_pay > 0 && !collect($incomes)->contains('name', 'Uang Lembur')) {
                    $incomes[] = [
                        'name' => 'Uang Lembur',
                        'amount' => (float) $payroll->total_overtime_pay,
                    ];
                }
            }

            $this->selectedIncomes = $incomes;
            $this->showIncomeModal = true;
        }
    }

    public function closeIncomeModal()
    {
        $this->showIncomeModal = false;
        $this->selectedPayrollId = null;
        $this->selectedIncomes = [];
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
