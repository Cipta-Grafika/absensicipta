<?php

namespace App\Services;

use App\Models\SavingSummary;
use App\Models\SavingTransaction;
use App\Models\SavingWithdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SavingTransactionService
{
    /**
     * Record or overwrite payroll syirkah transaction for a user and period month.
     * Prevents duplicate data per user per month by updating existing transaction if found.
     */
    public static function recordPayrollSyirkah(
        string $userId,
        string $savingsId,
        float $mandatoryAmount,
        float $secondaryAmount,
        string $periodMonth,
        ?string $payrollId = null
    ): SavingTransaction {
        return DB::transaction(function () use ($userId, $savingsId, $mandatoryAmount, $secondaryAmount, $periodMonth, $payrollId) {
            $description = 'Potongan Syirkah Payroll ' . $periodMonth;
            $periodDate = Carbon::parse($periodMonth . '-01')->endOfMonth();

            // Find existing transactions for this user & period month
            $query = SavingTransaction::where('user_id', $userId)
                ->where('savings_id', $savingsId)
                ->where(function ($q) use ($payrollId, $periodMonth, $description) {
                    if ($payrollId) {
                        $q->where(function ($sub) use ($payrollId) {
                            $sub->where('reference_type', 'payroll')
                                ->where('reference_id', $payrollId);
                        });
                    }
                    $q->orWhere('description', $description)
                      ->orWhere('description', 'like', '%Payroll ' . $periodMonth . '%');
                });

            $existingTransactions = $query->orderBy('created_at', 'desc')->get();

            if ($existingTransactions->isNotEmpty()) {
                // Keep the first existing transaction to update/overwrite
                $mainTx = $existingTransactions->first();

                // Delete any remaining duplicate transactions for this month
                $duplicates = $existingTransactions->slice(1);
                foreach ($duplicates as $dup) {
                    $dup->delete();
                }

                // Update the main transaction with accurate values (preserve approval if already approved)
                $mainTx->update([
                    'transaction_type' => 'deposit',
                    'mandatory_amount' => $mandatoryAmount,
                    'secondary_amount' => $secondaryAmount,
                    'reference_type' => 'payroll',
                    'reference_id' => $payrollId ?: $mainTx->reference_id,
                    'description' => $description,
                    'created_at' => $periodDate,
                    'updated_at' => now(),
                ]);

                $transaction = $mainTx;
            } else {
                // Create single transaction with pending status awaiting Syirkah role approval
                $transaction = SavingTransaction::create([
                    'user_id' => $userId,
                    'savings_id' => $savingsId,
                    'transaction_type' => 'deposit',
                    'mandatory_amount' => $mandatoryAmount,
                    'secondary_amount' => $secondaryAmount,
                    'reference_type' => 'payroll',
                    'reference_id' => $payrollId,
                    'description' => $description,
                    'status' => 'pending',
                    'approved_by' => null,
                    'approval_date' => null,
                    'created_at' => $periodDate,
                    'updated_at' => $periodDate,
                ]);
            }

            // Recalculate running balance chronologically (only includes approved transactions)
            self::recalculateUserTransactions($userId, $savingsId);

            return $transaction->fresh();
        });
    }

    /**
     * Chronologically recalculate running balances (balance_mandatory & balance_secondary)
     * for a given user and savings program. ONLY approved transactions contribute to the balance.
     */
    public static function recalculateUserTransactions(string $userId, ?string $savingsId = null): void
    {
        $query = SavingTransaction::where('user_id', $userId);
        if ($savingsId) {
            $query->where('savings_id', $savingsId);
        }

        // PERF-03: Use cursor() for memory-efficient streaming without loading all models to RAM
        $runningMandatory = 0.0;
        $runningSecondary = 0.0;

        foreach ($query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->cursor() as $tx) {
            if ($tx->status === 'approved') {
                if ($tx->transaction_type === 'deposit') {
                    $runningMandatory += (float) $tx->mandatory_amount;
                    $runningSecondary += (float) $tx->secondary_amount;
                } elseif ($tx->transaction_type === 'withdrawal') {
                    $runningMandatory -= (float) $tx->mandatory_amount;
                    $runningSecondary -= (float) $tx->secondary_amount;
                }

                // Ensure balance doesn't drop below 0 due to edge cases
                $runningMandatory = max(0.0, $runningMandatory);
                $runningSecondary = max(0.0, $runningSecondary);

                // Only issue SQL update if balances actually changed
                if (abs((float) $tx->balance_mandatory - $runningMandatory) > 0.001 || abs((float) $tx->balance_secondary - $runningSecondary) > 0.001) {
                    DB::table('saving_transactions')
                        ->where('id', $tx->id)
                        ->update([
                            'balance_mandatory' => $runningMandatory,
                            'balance_secondary' => $runningSecondary,
                        ]);
                }
            } else {
                // For pending or rejected transactions, keep balance as 0 or current running snapshot without incrementing
                if ((float) $tx->balance_mandatory != 0.0 || (float) $tx->balance_secondary != 0.0) {
                    DB::table('saving_transactions')
                        ->where('id', $tx->id)
                        ->update([
                            'balance_mandatory' => 0.0,
                            'balance_secondary' => 0.0,
                        ]);
                }
            }
        }

        // Update SavingSummary strictly from approved transactions
        $existingSummarySavingsIds = SavingSummary::where('user_id', $userId)->pluck('savings_id')->toArray();
        $txSavingsIds = SavingTransaction::where('user_id', $userId)->pluck('savings_id')->toArray();

        $distinctSavingsIds = $savingsId
            ? [$savingsId]
            : array_values(array_unique(array_merge($existingSummarySavingsIds, $txSavingsIds)));

        foreach ($distinctSavingsIds as $sId) {
            $depMan = (float) SavingTransaction::where('user_id', $userId)
                ->where('savings_id', $sId)
                ->where('status', 'approved')
                ->where('transaction_type', 'deposit')
                ->sum('mandatory_amount');

            $wdMan = (float) SavingTransaction::where('user_id', $userId)
                ->where('savings_id', $sId)
                ->where('status', 'approved')
                ->where('transaction_type', 'withdrawal')
                ->sum('mandatory_amount');

            $depSec = (float) SavingTransaction::where('user_id', $userId)
                ->where('savings_id', $sId)
                ->where('status', 'approved')
                ->where('transaction_type', 'deposit')
                ->sum('secondary_amount');

            $wdSec = (float) SavingTransaction::where('user_id', $userId)
                ->where('savings_id', $sId)
                ->where('status', 'approved')
                ->where('transaction_type', 'withdrawal')
                ->sum('secondary_amount');

            $totalMandatory = max(0.0, $depMan - $wdMan);
            $totalSecondary = max(0.0, $depSec - $wdSec);

            if ($totalMandatory == 0.0 && $totalSecondary == 0.0 && !SavingTransaction::where('user_id', $userId)->where('savings_id', $sId)->exists()) {
                SavingSummary::where('user_id', $userId)->where('savings_id', $sId)->delete();
            } else {
                SavingSummary::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'savings_id' => $sId,
                    ],
                    [
                        'total_mandatory' => $totalMandatory,
                        'total_secondary' => $totalSecondary,
                    ]
                );
            }
        }
    }

    /**
     * Synchronize and clean all SavingSummary records across all users in the system.
     */
    public static function syncAllSummaries(): void
    {
        $allUserIds = array_unique(array_merge(
            SavingTransaction::pluck('user_id')->toArray(),
            SavingSummary::pluck('user_id')->toArray()
        ));

        foreach ($allUserIds as $userId) {
            self::recalculateUserTransactions($userId);
        }
    }

    /**
     * Approve a saving transaction by a user with role Syirkah/Superadmin.
     */
    public static function approveTransaction(string $transactionId, string $approverId): SavingTransaction
    {
        return DB::transaction(function () use ($transactionId, $approverId) {
            $tx = SavingTransaction::lockForUpdate()->findOrFail($transactionId);
            $tx->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approval_date' => now(),
                'rejection_reason' => null,
                'updated_at' => now(),
            ]);

            self::recalculateUserTransactions($tx->user_id, $tx->savings_id);

            return $tx->fresh();
        });
    }

    /**
     * Reject a saving transaction by a user with role Syirkah/Superadmin.
     */
    public static function rejectTransaction(string $transactionId, string $approverId, ?string $reason = null): SavingTransaction
    {
        return DB::transaction(function () use ($transactionId, $approverId, $reason) {
            $tx = SavingTransaction::lockForUpdate()->findOrFail($transactionId);
            $tx->update([
                'status' => 'rejected',
                'approved_by' => $approverId,
                'approval_date' => now(),
                'rejection_reason' => $reason,
                'updated_at' => now(),
            ]);

            self::recalculateUserTransactions($tx->user_id, $tx->savings_id);

            return $tx->fresh();
        });
    }

    /**
     * Bulk approve multiple saving transactions.
     */
    public static function bulkApprove(array $transactionIds, string $approverId): int
    {
        if (empty($transactionIds)) return 0;

        return DB::transaction(function () use ($transactionIds, $approverId) {
            $affectedTransactions = SavingTransaction::whereIn('id', $transactionIds)->get();
            $affectedUsers = [];

            foreach ($affectedTransactions as $tx) {
                $tx->update([
                    'status' => 'approved',
                    'approved_by' => $approverId,
                    'approval_date' => now(),
                    'rejection_reason' => null,
                    'updated_at' => now(),
                ]);
                $affectedUsers[$tx->user_id] = $tx->savings_id;
            }

            foreach ($affectedUsers as $uId => $sId) {
                self::recalculateUserTransactions($uId, $sId);
            }

            return $affectedTransactions->count();
        });
    }

    /**
     * Bulk reject multiple saving transactions.
     */
    public static function bulkReject(array $transactionIds, string $approverId, ?string $reason = null): int
    {
        if (empty($transactionIds)) return 0;

        return DB::transaction(function () use ($transactionIds, $approverId, $reason) {
            $affectedTransactions = SavingTransaction::whereIn('id', $transactionIds)->get();
            $affectedUsers = [];

            foreach ($affectedTransactions as $tx) {
                $tx->update([
                    'status' => 'rejected',
                    'approved_by' => $approverId,
                    'approval_date' => now(),
                    'rejection_reason' => $reason,
                    'updated_at' => now(),
                ]);
                $affectedUsers[$tx->user_id] = $tx->savings_id;
            }

            foreach ($affectedUsers as $uId => $sId) {
                self::recalculateUserTransactions($uId, $sId);
            }

            return $affectedTransactions->count();
        });
    }

    /**
     * Delete a saving transaction permanently and recalculate balances.
     */
    public static function deleteTransaction(string $transactionId): bool
    {
        return DB::transaction(function () use ($transactionId) {
            $tx = SavingTransaction::lockForUpdate()->find($transactionId);
            if (!$tx) return false;

            $userId = $tx->user_id;
            $savingsId = $tx->savings_id;

            $tx->delete();

            self::recalculateUserTransactions($userId, $savingsId);

            return true;
        });
    }

    /**
     * Bulk delete saving transactions permanently and recalculate balances.
     */
    public static function bulkDelete(array $transactionIds): int
    {
        if (empty($transactionIds)) return 0;

        return DB::transaction(function () use ($transactionIds) {
            $affectedTransactions = SavingTransaction::whereIn('id', $transactionIds)->get();
            $affectedUsers = [];

            foreach ($affectedTransactions as $tx) {
                $affectedUsers[$tx->user_id] = $tx->savings_id;
                $tx->delete();
            }

            foreach ($affectedUsers as $uId => $sId) {
                self::recalculateUserTransactions($uId, $sId);
            }

            return $affectedTransactions->count();
        });
    }

    /**
     * Scan database and cleanup all existing duplicate payroll syirkah transactions.
     */
    public static function cleanupDuplicates(): int
    {
        $removedCount = 0;

        DB::transaction(function () use (&$removedCount) {
            $allTransactions = SavingTransaction::orderBy('created_at', 'asc')->get();

            // Group transactions by user_id and payroll period (extracted from description or created_at)
            $grouped = [];
            foreach ($allTransactions as $tx) {
                $month = null;
                if (preg_match('/Payroll\s+(\d{4}-\d{2})/i', $tx->description, $matches)) {
                    $month = $matches[1];
                } elseif ($tx->reference_type === 'payroll') {
                    $month = $tx->created_at ? $tx->created_at->format('Y-m') : null;
                }

                if ($month) {
                    $key = $tx->user_id . '_' . $tx->savings_id . '_' . $month;
                    $grouped[$key][] = $tx;
                }
            }

            foreach ($grouped as $key => $txList) {
                if (count($txList) > 1) {
                    // Normalize created_at date to end of period month for consistency
                    [$userId, $savingsId, $month] = explode('_', $key);
                    $periodDate = Carbon::parse($month . '-01')->endOfMonth();

                    // Keep the last created transaction
                    /** @var SavingTransaction $keep */
                    $keep = array_pop($txList);
                    $keep->update([
                        'created_at' => $periodDate,
                        'description' => 'Potongan Syirkah Payroll ' . $month,
                    ]);

                    // Delete duplicates
                    foreach ($txList as $dup) {
                        DB::table('saving_transactions')->where('id', $dup->id)->delete();
                        $removedCount++;
                    }
                }
            }

            // Fix timestamps of non-duplicate payroll transactions to proper end-of-month dates
            foreach ($allTransactions as $tx) {
                if (preg_match('/Payroll\s+(\d{4}-\d{2})/i', $tx->description, $matches)) {
                    $month = $matches[1];
                    $periodDate = Carbon::parse($month . '-01')->endOfMonth();
                    DB::table('saving_transactions')
                        ->where('id', $tx->id)
                        ->update(['created_at' => $periodDate]);
                }
            }

            // Recalculate all user balances
            $userIds = SavingTransaction::pluck('user_id')->unique();
            foreach ($userIds as $uId) {
                self::recalculateUserTransactions($uId);
            }
        });

        return $removedCount;
    }

    /**
     * Create a new withdrawal request for a user.
     */
    public static function createWithdrawalRequest(
        string $userId,
        string $savingsId,
        string $withdrawalType,
        float $mandatoryAmount,
        float $secondaryAmount,
        ?string $reason = null
    ): SavingWithdrawal {
        $totalAmount = $mandatoryAmount + $secondaryAmount;
        if ($totalAmount <= 0) {
            throw new InvalidArgumentException('Jumlah penarikan harus lebih dari 0.');
        }

        // Get current balance summary for validation
        $summary = SavingSummary::where('user_id', $userId)
            ->where('savings_id', $savingsId)
            ->first();

        $curMandatory = (float) ($summary?->total_mandatory ?? 0);
        $curSecondary = (float) ($summary?->total_secondary ?? 0);

        // Also check if there are pending withdrawals to prevent over-drafting
        $pendingMandatory = (float) SavingWithdrawal::where('user_id', $userId)
            ->where('savings_id', $savingsId)
            ->where('status', 'pending')
            ->sum('mandatory_amount');

        $pendingSecondary = (float) SavingWithdrawal::where('user_id', $userId)
            ->where('savings_id', $savingsId)
            ->where('status', 'pending')
            ->sum('secondary_amount');

        $availMandatory = max(0.0, $curMandatory - $pendingMandatory);
        $availSecondary = max(0.0, $curSecondary - $pendingSecondary);

        if ($mandatoryAmount > $availMandatory) {
            throw new InvalidArgumentException('Saldo Syirkah Wajib tidak mencukupi untuk pengajuan ini. Sisa saldo tersedia: Rp ' . number_format($availMandatory, 0, ',', '.'));
        }

        if ($secondaryAmount > $availSecondary) {
            throw new InvalidArgumentException('Saldo Syirkah SSR/Sukarela tidak mencukupi untuk pengajuan ini. Sisa saldo tersedia: Rp ' . number_format($availSecondary, 0, ',', '.'));
        }

        $withdrawal = DB::transaction(function () use ($userId, $savingsId, $withdrawalType, $mandatoryAmount, $secondaryAmount, $totalAmount, $reason) {
            return SavingWithdrawal::create([
                'user_id' => $userId,
                'savings_id' => $savingsId,
                'withdrawal_type' => $withdrawalType,
                'mandatory_amount' => $mandatoryAmount,
                'secondary_amount' => $secondaryAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'reason' => $reason,
                'approved_by' => null,
                'approved_at' => null,
                'paid_by' => null,
                'paid_at' => null,
                'rejection_reason' => null,
                'saving_transaction_id' => null,
            ]);
        });

        // Send Telegram Notification to Manager / Admin Divisi
        try {
            TelegramNotificationService::notifyPendingWithdrawal($withdrawal);
        } catch (\Throwable $e) {
            // Safe-fail: Do not let notification failure block transaction
        }

        return $withdrawal;
    }

    /**
     * Approve a saving withdrawal request (Status: ACCEPTED).
     * Automatically creates a withdrawal SavingTransaction in the mutation ledger.
     */
    public static function approveWithdrawalRequest(string $withdrawalId, string $approverId): SavingWithdrawal
    {
        $approvedWithdrawal = DB::transaction(function () use ($withdrawalId, $approverId) {
            $withdrawal = SavingWithdrawal::lockForUpdate()->findOrFail($withdrawalId);

            if ($withdrawal->status === 'accepted' || $withdrawal->status === 'paid') {
                return $withdrawal;
            }

            // Create SavingTransaction (Withdrawal debit from syirkah account)
            $typeLabel = match ($withdrawal->withdrawal_type) {
                'full' => 'Syirkah Full',
                'mandatory' => 'Syirkah Wajib',
                'secondary' => 'Syirkah SSR',
                default => 'Syirkah',
            };

            $desc = 'Penarikan ' . $typeLabel . ($withdrawal->reason ? ' (' . $withdrawal->reason . ')' : '');

            // If an existing transaction was linked, update or create new
            if ($withdrawal->saving_transaction_id) {
                $transaction = SavingTransaction::find($withdrawal->saving_transaction_id);
                if ($transaction) {
                    $transaction->update([
                        'transaction_type' => 'withdrawal',
                        'mandatory_amount' => $withdrawal->mandatory_amount,
                        'secondary_amount' => $withdrawal->secondary_amount,
                        'description' => $desc,
                        'status' => 'approved',
                        'approved_by' => $approverId,
                        'approval_date' => now(),
                    ]);
                } else {
                    $transaction = SavingTransaction::create([
                        'user_id' => $withdrawal->user_id,
                        'savings_id' => $withdrawal->savings_id,
                        'transaction_type' => 'withdrawal',
                        'mandatory_amount' => $withdrawal->mandatory_amount,
                        'secondary_amount' => $withdrawal->secondary_amount,
                        'reference_type' => 'saving_withdrawal',
                        'reference_id' => $withdrawal->id,
                        'description' => $desc,
                        'status' => 'approved',
                        'approved_by' => $approverId,
                        'approval_date' => now(),
                    ]);
                }
            } else {
                $transaction = SavingTransaction::create([
                    'user_id' => $withdrawal->user_id,
                    'savings_id' => $withdrawal->savings_id,
                    'transaction_type' => 'withdrawal',
                    'mandatory_amount' => $withdrawal->mandatory_amount,
                    'secondary_amount' => $withdrawal->secondary_amount,
                    'reference_type' => 'saving_withdrawal',
                    'reference_id' => $withdrawal->id,
                    'description' => $desc,
                    'status' => 'approved',
                    'approved_by' => $approverId,
                    'approval_date' => now(),
                ]);
            }

            $withdrawal->update([
                'status' => 'accepted',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'rejection_reason' => null,
                'saving_transaction_id' => $transaction->id,
            ]);

            // Recalculate balances
            self::recalculateUserTransactions($withdrawal->user_id, $withdrawal->savings_id);

            return $withdrawal->fresh();
        });

        // Send Telegram Notification to Owner / Syirkah Team / Finance
        try {
            TelegramNotificationService::notifyAcceptedWithdrawal($approvedWithdrawal);
        } catch (\Throwable $e) {
            // Safe-fail
        }

        return $approvedWithdrawal;
    }

    /**
     * Reject a saving withdrawal request (Status: REJECTED).
     */
    public static function rejectWithdrawalRequest(string $withdrawalId, string $approverId, ?string $reason = null): SavingWithdrawal
    {
        return DB::transaction(function () use ($withdrawalId, $approverId, $reason) {
            $withdrawal = SavingWithdrawal::lockForUpdate()->findOrFail($withdrawalId);

            // If a transaction was previously created (e.g. accepted before), delete it
            if ($withdrawal->saving_transaction_id) {
                SavingTransaction::where('id', $withdrawal->saving_transaction_id)->delete();
            }

            $withdrawal->update([
                'status' => 'rejected',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'rejection_reason' => $reason,
                'saving_transaction_id' => null,
            ]);

            // Recalculate balances
            self::recalculateUserTransactions($withdrawal->user_id, $withdrawal->savings_id);

            return $withdrawal->fresh();
        });
    }

    /**
     * Mark an approved withdrawal request as PAID (Status: PAID).
     */
    public static function markAsPaidWithdrawalRequest(string $withdrawalId, string $payerId): SavingWithdrawal
    {
        $paidWithdrawal = DB::transaction(function () use ($withdrawalId, $payerId) {
            $withdrawal = SavingWithdrawal::lockForUpdate()->findOrFail($withdrawalId);

            // If not yet accepted, approve first to record the transaction
            if ($withdrawal->status === 'pending') {
                self::approveWithdrawalRequest($withdrawalId, $payerId);
                $withdrawal = $withdrawal->fresh();
            }

            $withdrawal->update([
                'status' => 'paid',
                'paid_by' => $payerId,
                'paid_at' => now(),
            ]);

            return $withdrawal->fresh();
        });

        // Send Telegram Notification for PAID withdrawal (to Employee and Division Admin)
        try {
            TelegramNotificationService::notifyPaidWithdrawal($paidWithdrawal);
        } catch (\Throwable $e) {
            // Safe-fail
        }

        return $paidWithdrawal;
    }

    /**
     * Delete a withdrawal request.
     */
    public static function deleteWithdrawalRequest(string $withdrawalId): bool
    {
        return DB::transaction(function () use ($withdrawalId) {
            $withdrawal = SavingWithdrawal::lockForUpdate()->find($withdrawalId);
            if (!$withdrawal) return false;

            if ($withdrawal->saving_transaction_id) {
                SavingTransaction::where('id', $withdrawal->saving_transaction_id)->delete();
            }

            $userId = $withdrawal->user_id;
            $savingsId = $withdrawal->savings_id;

            $withdrawal->delete();

            self::recalculateUserTransactions($userId, $savingsId);

            return true;
        });
    }
}

