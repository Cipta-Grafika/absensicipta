<?php

namespace App\Services;

use App\Models\SavingSummary;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

                // Update the main transaction with accurate values
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
                // Create single transaction
                $transaction = SavingTransaction::create([
                    'user_id' => $userId,
                    'savings_id' => $savingsId,
                    'transaction_type' => 'deposit',
                    'mandatory_amount' => $mandatoryAmount,
                    'secondary_amount' => $secondaryAmount,
                    'reference_type' => 'payroll',
                    'reference_id' => $payrollId,
                    'description' => $description,
                    'created_at' => $periodDate,
                    'updated_at' => $periodDate,
                ]);
            }

            // Recalculate running balance chronologically
            self::recalculateUserTransactions($userId, $savingsId);

            return $transaction->fresh();
        });
    }

    /**
     * Chronologically recalculate running balances (balance_mandatory & balance_secondary)
     * for a given user and savings program.
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
            if ((float) $tx->balance_mandatory !== $runningMandatory || (float) $tx->balance_secondary !== $runningSecondary) {
                DB::table('saving_transactions')
                    ->where('id', $tx->id)
                    ->update([
                        'balance_mandatory' => $runningMandatory,
                        'balance_secondary' => $runningSecondary,
                    ]);
            }
        }

        // Update SavingSummary
        $distinctSavingsIds = $savingsId
            ? [$savingsId]
            : SavingTransaction::where('user_id', $userId)->pluck('savings_id')->unique();

        foreach ($distinctSavingsIds as $sId) {
            $depMan = SavingTransaction::where('user_id', $userId)->where('savings_id', $sId)->where('transaction_type', 'deposit')->sum('mandatory_amount');
            $wdMan = SavingTransaction::where('user_id', $userId)->where('savings_id', $sId)->where('transaction_type', 'withdrawal')->sum('mandatory_amount');

            $depSec = SavingTransaction::where('user_id', $userId)->where('savings_id', $sId)->where('transaction_type', 'deposit')->sum('secondary_amount');
            $wdSec = SavingTransaction::where('user_id', $userId)->where('savings_id', $sId)->where('transaction_type', 'withdrawal')->sum('secondary_amount');

            SavingSummary::updateOrCreate(
                [
                    'user_id' => $userId,
                    'savings_id' => $sId,
                ],
                [
                    'total_mandatory' => max(0, $depMan - $wdMan),
                    'total_secondary' => max(0, $depSec - $wdSec),
                ]
            );
        }
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
}
