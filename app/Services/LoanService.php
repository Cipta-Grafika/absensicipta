<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Saving;
use App\Models\SavingTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoanService
{
    /**
     * Recalculate and synchronize a specific loan's remaining balance, status, installments, and syirkah deposits.
     */
    public static function syncLoan(Loan|string $loan): Loan
    {
        if (is_string($loan)) {
            $loan = Loan::findOrFail($loan);
        }

        return DB::transaction(function () use ($loan) {
            $savingProgram = Saving::first();
            $affectedUser = false;

            // 1. Sync pending installments where parent payroll is already paid
            $installments = LoanInstallment::where('loan_id', $loan->id)->with('payroll')->get();
            foreach ($installments as $inst) {
                if ($inst->payroll && $inst->payroll->status === 'paid' && $inst->status !== 'paid') {
                    $inst->update(['status' => 'paid']);
                } elseif ($inst->payroll && $inst->payroll->status !== 'paid' && $inst->status === 'paid') {
                    $inst->update(['status' => 'pending']);
                }

                // Ensure Syirkah deposit transaction is created for paid installments if required
                if ($inst->status === 'paid' && $loan->syirkah_destination && !in_array($loan->syirkah_destination, ['none', 'company_cash']) && $savingProgram) {
                    if (!$inst->saving_transaction_id || !SavingTransaction::where('id', $inst->saving_transaction_id)->exists()) {
                        $mandAmount = in_array($loan->syirkah_destination, ['syirkah_mandatory', 'syirkah_pool_mandatory']) ? (float) $inst->amount_paid : 0.0;
                        $secAmount = in_array($loan->syirkah_destination, ['syirkah_secondary', 'syirkah_pool', 'syirkah_pool_secondary']) ? (float) $inst->amount_paid : 0.0;

                        $desc = match ($loan->syirkah_destination) {
                            'syirkah_pool_secondary', 'syirkah_pool' => 'Pengembalian Talangan Kas Bersama SSR (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                            'syirkah_pool_mandatory' => 'Pengembalian Talangan Kas Bersama Wajib (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                            'syirkah_mandatory' => 'Setoran Tabungan Syirkah Wajib Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                            default => 'Setoran Tabungan Syirkah SSR Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                        };

                        $tx = SavingTransaction::create([
                            'user_id' => $loan->user_id,
                            'savings_id' => $savingProgram->id,
                            'transaction_type' => 'deposit',
                            'mandatory_amount' => $mandAmount,
                            'secondary_amount' => $secAmount,
                            'status' => 'approved',
                            'period_month' => $inst->payroll?->period_month ?? now()->format('Y-m'),
                            'reference_type' => 'loan_installment',
                            'reference_id' => $inst->id,
                            'description' => trim($desc),
                            'approved_by' => Auth::id() ?? $loan->approved_by,
                            'approval_date' => now(),
                        ]);

                        $inst->update(['saving_transaction_id' => $tx->id]);
                        $affectedUser = true;
                    }
                }
            }

            // 2. Fallback check: Look for paid payroll details that match this loan if installments were missing
            if ($loan->payment_source === 'payroll') {
                $paidPayrolls = Payroll::where('employee_id', $loan->user_id)
                    ->where('status', 'paid')
                    ->with('details')
                    ->get();

                foreach ($paidPayrolls as $p) {
                    $hasInstallment = LoanInstallment::where('loan_id', $loan->id)
                        ->where('payroll_id', $p->id)
                        ->exists();

                    if (!$hasInstallment) {
                        // Check if payroll details contain a deduction matching this loan
                        foreach ($p->details->where('type', 'deduction') as $d) {
                            $nameLower = strtolower($d->name);
                            $matches = str_contains($nameLower, 'pinjaman') || 
                                       str_contains($nameLower, 'kasbon') || 
                                       ($loan->description && str_contains($nameLower, strtolower($loan->description)));

                            if ($matches && (float) $d->amount > 0) {
                                // Check if another loan already claimed this detail
                                $claimed = LoanInstallment::where('payroll_id', $p->id)
                                    ->where('amount_paid', $d->amount)
                                    ->exists();

                                if (!$claimed) {
                                    $newInst = LoanInstallment::create([
                                        'loan_id' => $loan->id,
                                        'amount_paid' => (float) $d->amount,
                                        'payment_method' => 'payroll_deduction',
                                        'payroll_id' => $p->id,
                                        'status' => 'paid',
                                    ]);

                                    if ($loan->syirkah_destination && !in_array($loan->syirkah_destination, ['none', 'company_cash']) && $savingProgram) {
                                        $mandAmount = in_array($loan->syirkah_destination, ['syirkah_mandatory', 'syirkah_pool_mandatory']) ? (float) $d->amount : 0.0;
                                        $secAmount = in_array($loan->syirkah_destination, ['syirkah_secondary', 'syirkah_pool', 'syirkah_pool_secondary']) ? (float) $d->amount : 0.0;

                                        $desc = match ($loan->syirkah_destination) {
                                            'syirkah_pool_secondary', 'syirkah_pool' => 'Pengembalian Talangan Kas Bersama SSR (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $p->period_month,
                                            'syirkah_pool_mandatory' => 'Pengembalian Talangan Kas Bersama Wajib (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $p->period_month,
                                            'syirkah_mandatory' => 'Setoran Tabungan Syirkah Wajib Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $p->period_month,
                                            default => 'Setoran Tabungan Syirkah SSR Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $p->period_month,
                                        };

                                        $tx = SavingTransaction::create([
                                            'user_id' => $loan->user_id,
                                            'savings_id' => $savingProgram->id,
                                            'transaction_type' => 'deposit',
                                            'mandatory_amount' => $mandAmount,
                                            'secondary_amount' => $secAmount,
                                            'status' => 'approved',
                                            'period_month' => $p->period_month,
                                            'reference_type' => 'loan_installment',
                                            'reference_id' => $newInst->id,
                                            'description' => trim($desc),
                                            'approved_by' => Auth::id() ?? $loan->approved_by,
                                            'approval_date' => now(),
                                        ]);

                                        $newInst->update(['saving_transaction_id' => $tx->id]);
                                        $affectedUser = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // 3. Recalculate remaining balance & status
            $totalPaid = (float) LoanInstallment::where('loan_id', $loan->id)
                ->where('status', 'paid')
                ->sum('amount_paid');

            $newRemaining = max(0.0, (float) $loan->loan_amount - $totalPaid);

            $newStatus = $loan->status;
            if ($newRemaining <= 0.0) {
                $newStatus = 'paid_off';
            } elseif ($loan->status === 'paid_off' || $loan->status === 'approved') {
                $newStatus = 'active';
            }

            $loan->update([
                'remaining_balance' => $newRemaining,
                'status' => $newStatus,
            ]);

            if ($affectedUser) {
                SavingTransactionService::recalculateUserTransactions($loan->user_id);
            }

            return $loan->fresh();
        });
    }

    /**
     * Synchronize all loans for a specific user.
     */
    public static function syncUserLoans(string|int $userId): void
    {
        $loans = Loan::where('user_id', $userId)->get();
        foreach ($loans as $loan) {
            self::syncLoan($loan);
        }
        SavingTransactionService::recalculateUserTransactions((string) $userId);
    }

    /**
     * Synchronize all loans across the entire system.
     */
    public static function syncAllLoans(): void
    {
        $loans = Loan::all();
        $affectedUsers = [];

        foreach ($loans as $loan) {
            self::syncLoan($loan);
            $affectedUsers[] = $loan->user_id;
        }

        foreach (array_unique(array_filter($affectedUsers)) as $uId) {
            SavingTransactionService::recalculateUserTransactions($uId);
        }
    }

    /**
     * Process loan deductions and syirkah deposits when a payroll is marked as PAID.
     */
    public static function processPayrollPaid(Payroll $payroll): void
    {
        DB::transaction(function () use ($payroll) {
            $savingProgram = Saving::first();
            $installments = LoanInstallment::where('payroll_id', $payroll->id)->with('loan')->get();

            foreach ($installments as $inst) {
                $inst->update(['status' => 'paid']);
                $loan = $inst->loan;

                if ($loan) {
                    self::syncLoan($loan);

                    // Create Syirkah transaction if configured and not yet created
                    if ($loan->syirkah_destination && !in_array($loan->syirkah_destination, ['none', 'company_cash']) && $savingProgram) {
                        if (!$inst->saving_transaction_id || !SavingTransaction::where('id', $inst->saving_transaction_id)->exists()) {
                            $mandAmount = in_array($loan->syirkah_destination, ['syirkah_mandatory', 'syirkah_pool_mandatory']) ? (float) $inst->amount_paid : 0.0;
                            $secAmount = in_array($loan->syirkah_destination, ['syirkah_secondary', 'syirkah_pool', 'syirkah_pool_secondary']) ? (float) $inst->amount_paid : 0.0;

                            $desc = match ($loan->syirkah_destination) {
                                'syirkah_pool_secondary', 'syirkah_pool' => 'Pengembalian Talangan Kas Bersama SSR (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $payroll->period_month,
                                'syirkah_pool_mandatory' => 'Pengembalian Talangan Kas Bersama Wajib (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $payroll->period_month,
                                'syirkah_mandatory' => 'Setoran Tabungan Syirkah Wajib Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $payroll->period_month,
                                default => 'Setoran Tabungan Syirkah SSR Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $payroll->period_month,
                            };

                            $tx = SavingTransaction::create([
                                'user_id' => $loan->user_id,
                                'savings_id' => $savingProgram->id,
                                'transaction_type' => 'deposit',
                                'mandatory_amount' => $mandAmount,
                                'secondary_amount' => $secAmount,
                                'status' => 'approved',
                                'period_month' => $payroll->period_month,
                                'reference_type' => 'loan_installment',
                                'reference_id' => $inst->id,
                                'description' => trim($desc),
                                'approved_by' => Auth::id(),
                                'approval_date' => now(),
                            ]);

                            $inst->update(['saving_transaction_id' => $tx->id]);
                        }
                    }
                }
            }

            // Sync all loans for this employee to guarantee 100% data integrity
            self::syncUserLoans($payroll->employee_id);
        });
    }

    /**
     * Rollback loan deductions and syirkah deposits when a payroll is deleted or returned to draft.
     */
    public static function processPayrollRollback(Payroll|string $payroll): void
    {
        $payrollId = is_string($payroll) ? $payroll : $payroll->id;
        $employeeId = is_string($payroll) ? Payroll::find($payrollId)?->employee_id : $payroll->employee_id;

        DB::transaction(function () use ($payrollId, $employeeId) {
            $installments = LoanInstallment::where('payroll_id', $payrollId)->with('loan')->get();

            foreach ($installments as $inst) {
                if ($inst->saving_transaction_id) {
                    SavingTransaction::where('id', $inst->saving_transaction_id)->delete();
                }

                $loanId = $inst->loan_id;
                $inst->delete();

                if ($loanId) {
                    self::syncLoan($loanId);
                }
            }

            if ($employeeId) {
                self::syncUserLoans($employeeId);
            }
        });
    }
}
