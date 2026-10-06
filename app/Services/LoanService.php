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

                // Handle Syirkah deposit transaction for paid installments
                if ($inst->status === 'paid' && $savingProgram) {
                    $isPersonalDest = in_array($loan->syirkah_destination, ['syirkah_mandatory', 'syirkah_secondary']);
                    $isPoolDest = in_array($loan->syirkah_destination, ['syirkah_pool', 'syirkah_pool_secondary', 'syirkah_pool_mandatory']);

                    if ($isPersonalDest) {
                        $mandAmount = ($loan->syirkah_destination === 'syirkah_mandatory') ? (float) $inst->amount_paid : 0.0;
                        $secAmount = ($loan->syirkah_destination === 'syirkah_secondary') ? (float) $inst->amount_paid : 0.0;
                        $desc = match ($loan->syirkah_destination) {
                            'syirkah_mandatory' => 'Setoran Tabungan Syirkah Wajib Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                            default => 'Setoran Tabungan Syirkah SSR Pribadi (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                        };

                        if (!$inst->saving_transaction_id || !SavingTransaction::where('id', $inst->saving_transaction_id)->exists()) {
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
                    } elseif ($isPoolDest) {
                        $mandAmount = ($loan->syirkah_destination === 'syirkah_pool_mandatory') ? (float) $inst->amount_paid : 0.0;
                        $secAmount = ($loan->syirkah_destination !== 'syirkah_pool_mandatory') ? (float) $inst->amount_paid : 0.0;
                        $desc = match ($loan->syirkah_destination) {
                            'syirkah_pool_mandatory' => 'Pengembalian Kas Talangan Syirkah Wajib (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                            default => 'Pengembalian Kas Talangan Syirkah Sukarela (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . ($inst->payroll?->period_month ?? ''),
                        };

                        if (!$inst->saving_transaction_id || !SavingTransaction::where('id', $inst->saving_transaction_id)->exists()) {
                            $tx = SavingTransaction::create([
                                'user_id' => $loan->user_id,
                                'savings_id' => $savingProgram->id,
                                'transaction_type' => 'deposit',
                                'mandatory_amount' => $mandAmount,
                                'secondary_amount' => $secAmount,
                                'status' => 'approved',
                                'period_month' => $inst->payroll?->period_month ?? now()->format('Y-m'),
                                'reference_type' => 'loan_installment_pool',
                                'reference_id' => $inst->id,
                                'description' => trim($desc),
                                'approved_by' => Auth::id() ?? $loan->approved_by,
                                'approval_date' => now(),
                            ]);
                            $inst->update(['saving_transaction_id' => $tx->id]);
                            $affectedUser = true;
                        }
                    } elseif ($inst->saving_transaction_id) {
                        SavingTransaction::where('id', $inst->saving_transaction_id)->delete();
                        $inst->update(['saving_transaction_id' => null]);
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

                                    $isPersonalDest = in_array($loan->syirkah_destination, ['syirkah_mandatory', 'syirkah_secondary']);
                                    $isPoolDest = in_array($loan->syirkah_destination, ['syirkah_pool', 'syirkah_pool_secondary', 'syirkah_pool_mandatory']);

                                    if ($isPersonalDest && $savingProgram) {
                                        $mandAmount = ($loan->syirkah_destination === 'syirkah_mandatory') ? (float) $d->amount : 0.0;
                                        $secAmount = ($loan->syirkah_destination === 'syirkah_secondary') ? (float) $d->amount : 0.0;
                                        $desc = match ($loan->syirkah_destination) {
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
                                    } elseif ($isPoolDest && $savingProgram) {
                                        $mandAmount = ($loan->syirkah_destination === 'syirkah_pool_mandatory') ? (float) $d->amount : 0.0;
                                        $secAmount = ($loan->syirkah_destination !== 'syirkah_pool_mandatory') ? (float) $d->amount : 0.0;
                                        $desc = match ($loan->syirkah_destination) {
                                            'syirkah_pool_mandatory' => 'Pengembalian Kas Talangan Syirkah Wajib (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $p->period_month,
                                            default => 'Pengembalian Kas Talangan Syirkah Sukarela (' . ($loan->description ?: 'Pinjaman') . ') via Payroll ' . $p->period_month,
                                        };

                                        $tx = SavingTransaction::create([
                                            'user_id' => $loan->user_id,
                                            'savings_id' => $savingProgram->id,
                                            'transaction_type' => 'deposit',
                                            'mandatory_amount' => $mandAmount,
                                            'secondary_amount' => $secAmount,
                                            'status' => 'approved',
                                            'period_month' => $p->period_month,
                                            'reference_type' => 'loan_installment_pool',
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

            // 3. Ensure disbursement transaction exists on central syirkah ledger
            if (in_array($loan->status, ['active', 'approved', 'paid_off']) && $savingProgram) {
                $isPersonalDisb = in_array($loan->disbursement_source, ['syirkah_mandatory', 'syirkah_secondary', 'syirkah_all']);
                $isPoolDisb = in_array($loan->disbursement_source, ['syirkah_pool', 'syirkah_pool_secondary', 'syirkah_pool_mandatory']);

                if ($isPoolDisb) {
                    $mandAmount = ($loan->disbursement_source === 'syirkah_pool_mandatory') ? (float) $loan->loan_amount : 0.0;
                    $secAmount = ($loan->disbursement_source !== 'syirkah_pool_mandatory') ? (float) $loan->loan_amount : 0.0;
                    $desc = ($loan->disbursement_source === 'syirkah_pool_mandatory' ? 'Pencairan Pinjaman (Kas Talangan Syirkah Wajib): ' : 'Pencairan Pinjaman (Kas Talangan Syirkah Sukarela): ') . ($loan->description ?: 'Pinjaman');

                    if ($loan->saving_transaction_id) {
                        $tx = SavingTransaction::find($loan->saving_transaction_id);
                        if ($tx) {
                            $tx->update([
                                'user_id' => $loan->user_id,
                                'mandatory_amount' => $mandAmount,
                                'secondary_amount' => $secAmount,
                                'reference_type' => 'loan_disbursement_pool',
                                'description' => $desc,
                                'status' => 'approved',
                            ]);
                        } else {
                            $tx = SavingTransaction::create([
                                'user_id' => $loan->user_id,
                                'savings_id' => $savingProgram->id,
                                'transaction_type' => 'withdrawal',
                                'mandatory_amount' => $mandAmount,
                                'secondary_amount' => $secAmount,
                                'status' => 'approved',
                                'period_month' => $loan->created_at ? $loan->created_at->format('Y-m') : now()->format('Y-m'),
                                'reference_type' => 'loan_disbursement_pool',
                                'reference_id' => $loan->id,
                                'description' => $desc,
                                'approved_by' => $loan->approved_by ?? Auth::id(),
                                'approval_date' => $loan->approval_date ?? now(),
                            ]);
                            $loan->update(['saving_transaction_id' => $tx->id]);
                        }
                    } else {
                        $tx = SavingTransaction::create([
                            'user_id' => $loan->user_id,
                            'savings_id' => $savingProgram->id,
                            'transaction_type' => 'withdrawal',
                            'mandatory_amount' => $mandAmount,
                            'secondary_amount' => $secAmount,
                            'status' => 'approved',
                            'period_month' => $loan->created_at ? $loan->created_at->format('Y-m') : now()->format('Y-m'),
                            'reference_type' => 'loan_disbursement_pool',
                            'reference_id' => $loan->id,
                            'description' => $desc,
                            'approved_by' => $loan->approved_by ?? Auth::id(),
                            'approval_date' => $loan->approval_date ?? now(),
                        ]);
                        $loan->update(['saving_transaction_id' => $tx->id]);
                    }
                    $affectedUser = true;
                }
            }

            // 4. Recalculate remaining balance & status
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
     * Synchronize and standardize pool transactions.
     */
    public static function cleanInvalidPoolTransactions(): void
    {
        DB::transaction(function () {
            $poolDisbursementTypes = ['syirkah_pool', 'syirkah_pool_secondary', 'syirkah_pool_mandatory'];
            $poolDestinationTypes = ['syirkah_pool', 'syirkah_pool_secondary', 'syirkah_pool_mandatory'];

            $affectedUserIds = [];

            // 1. Standardize pool disbursement transactions
            $poolLoans = Loan::whereIn('disbursement_source', $poolDisbursementTypes)->whereIn('status', ['active', 'approved', 'paid_off'])->get();
            foreach ($poolLoans as $loan) {
                self::syncLoan($loan);
                $affectedUserIds[] = $loan->user_id;
            }

            // Clean orphan pool disbursements
            $orphanDisbursements = SavingTransaction::whereIn('reference_type', ['loan_disbursement_pool', 'loan_disbursement'])->get();
            foreach ($orphanDisbursements as $tx) {
                $loan = Loan::find($tx->reference_id);
                if (!$loan) {
                    $affectedUserIds[] = $tx->user_id;
                    $tx->delete();
                } elseif (in_array($loan->disbursement_source, $poolDisbursementTypes) && $tx->reference_type !== 'loan_disbursement_pool') {
                    $tx->update(['reference_type' => 'loan_disbursement_pool']);
                    $affectedUserIds[] = $tx->user_id;
                }
            }

            // 2. Standardize pool installment deposits
            $orphanInstallments = SavingTransaction::whereIn('reference_type', ['loan_installment_pool', 'loan_installment'])->get();
            foreach ($orphanInstallments as $tx) {
                $inst = LoanInstallment::with('loan')->find($tx->reference_id);
                if (!$inst || !$inst->loan) {
                    $affectedUserIds[] = $tx->user_id;
                    $tx->delete();
                } elseif (in_array($inst->loan->syirkah_destination, $poolDestinationTypes) && $tx->reference_type !== 'loan_installment_pool') {
                    $tx->update(['reference_type' => 'loan_installment_pool']);
                    $affectedUserIds[] = $tx->user_id;
                }
            }

            // 3. Recalculate all affected users
            foreach (array_unique(array_filter($affectedUserIds)) as $uId) {
                SavingTransactionService::recalculateUserTransactions((string) $uId);
            }
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
