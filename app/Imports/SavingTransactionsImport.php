<?php

namespace App\Imports;

use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Services\SavingTransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SavingTransactionsImport implements ToCollection, WithHeadingRow
{
    /**
     * Parse flexible date formats from Excel (serial numbers, d/m/y, d/m/Y, Y-m-d, etc.)
     */
    public static function parseFlexibleDate($rawDate): Carbon
    {
        if (empty($rawDate)) {
            return now();
        }

        // 1. If it is an Excel numeric date timestamp
        if (is_numeric($rawDate) && $rawDate > 1000) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate));
            } catch (\Throwable $e) {}
        }

        $str = trim((string) $rawDate);

        // 2. Common Indonesian & Excel formatted date strings
        $formats = [
            'd/m/y', 'd/m/Y', 'd-m-y', 'd-m-Y',
            'Y-m-d', 'Y/m/d', 'Y.m.d', 'd.m.Y', 'd.m.y',
            'j/n/y', 'j/n/Y', 'j-n-y', 'j-n-Y',
            'Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i',
            'd/m/y H:i:s', 'd/m/y H:i',
        ];

        foreach ($formats as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, $str);
                if ($parsed !== false && $parsed->year >= 1970 && $parsed->year <= 2100) {
                    return $parsed;
                }
            } catch (\Throwable $e) {}
        }

        // 3. Fallback to standard Carbon::parse
        try {
            return Carbon::parse($str);
        } catch (\Throwable $e) {
            return now();
        }
    }

    /**
     * Clean numeric string or float from Excel
     */
    public static function parseAmount($rawAmount): float
    {
        if (is_numeric($rawAmount)) {
            return (float) $rawAmount;
        }

        if (empty($rawAmount)) {
            return 0.0;
        }

        $clean = preg_replace('/[^\d]/', '', (string) $rawAmount);
        return (float) ($clean ?: 0);
    }

    public function collection(Collection $rows)
    {
        $affectedUserIds = [];

        DB::transaction(function () use ($rows, &$affectedUserIds) {
            $defaultSaving = Saving::first();
            $rowNum = 1; // Row 1 is header

            foreach ($rows as $row) {
                $rowNum++;

                $nip = trim((string) ($row['nip'] ?? ''));
                $empName = trim((string) ($row['nama_karyawan'] ?? $row['nama'] ?? ''));
                $savingName = trim((string) ($row['nama_syirkah'] ?? $row['syirkah'] ?? ''));
                $rawDate = $row['tanggal'] ?? $row['date'] ?? null;
                $rawType = strtolower(trim((string) ($row['tipe_transaksi'] ?? $row['tipe'] ?? 'deposit')));
                $mandatory = self::parseAmount($row['mutasi_wajib'] ?? $row['wajib'] ?? 0);
                $secondary = self::parseAmount($row['mutasi_sukarela'] ?? $row['sukarela'] ?? 0);
                $description = trim((string) ($row['keterangan'] ?? ''));

                // Skip trailing empty rows
                if (empty($nip) && empty($empName) && empty($rawDate) && $mandatory == 0 && $secondary == 0) {
                    continue;
                }

                if (empty($nip) && empty($empName)) {
                    continue;
                }

                // Match User (only regular employees with group 'user' and active/suspend status)
                $user = null;
                if (!empty($nip)) {
                    $user = User::onlyWorkingEmployee()->where('nip', $nip)->first();
                }
                if (!$user && !empty($empName)) {
                    $user = User::onlyWorkingEmployee()
                        ->where(function ($q) use ($empName) {
                            $q->where('name', $empName)
                              ->orWhere('name', 'like', "%{$empName}%")
                              ->orWhere(DB::raw('LOWER(name)'), strtolower($empName));
                        })
                        ->first();
                }

                if (!$user) {
                    throw new \Exception("Baris {$rowNum}: Karyawan '" . ($empName ?: $nip) . "' tidak ditemukan dalam sistem.");
                }

                // Match Saving Program
                $saving = null;
                if (!empty($savingName)) {
                    $saving = Saving::where('savings_name', $savingName)
                        ->orWhere('savings_name', 'like', "%{$savingName}%")
                        ->orWhere(DB::raw('LOWER(savings_name)'), strtolower($savingName))
                        ->first();
                }
                if (!$saving) {
                    $saving = $defaultSaving;
                }

                if (!$saving) {
                    throw new \Exception("Baris {$rowNum}: Program Syirkah '" . ($savingName ?: 'Default') . "' tidak ditemukan.");
                }

                // Parse Date flexibly
                $txDate = self::parseFlexibleDate($rawDate);

                // Determine transaction type
                $txType = 'deposit';
                if (
                    str_contains($rawType, 'tarik') ||
                    str_contains($rawType, 'pencairan') ||
                    str_contains($rawType, 'withdraw') ||
                    str_contains($rawType, 'keluar') ||
                    str_contains($rawType, 'kredit')
                ) {
                    $txType = 'withdrawal';
                }

                // Check existing identical transaction to overwrite
                $dateStr = $txDate->format('Y-m-d');
                $existingTx = SavingTransaction::where('user_id', $user->id)
                    ->where('savings_id', $saving->id)
                    ->whereDate('created_at', $dateStr)
                    ->where(function ($q) use ($description) {
                        if (!empty($description)) {
                            $q->where('description', $description);
                        }
                    })
                    ->first();

                if ($existingTx) {
                    $existingTx->transaction_type = $txType;
                    $existingTx->mandatory_amount = $mandatory;
                    $existingTx->secondary_amount = $secondary;
                    $existingTx->status = 'approved';
                    $existingTx->approved_by = auth()->id() ?: $existingTx->approved_by;
                    $existingTx->approval_date = $existingTx->approval_date ?: now();
                    $existingTx->description = $description ?: $existingTx->description;
                    $existingTx->created_at = $txDate;
                    $existingTx->updated_at = now();
                    $existingTx->save();
                } else {
                    $newTx = new SavingTransaction([
                        'user_id' => $user->id,
                        'savings_id' => $saving->id,
                        'transaction_type' => $txType,
                        'mandatory_amount' => $mandatory,
                        'secondary_amount' => $secondary,
                        'balance_mandatory' => 0,
                        'balance_secondary' => 0,
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approval_date' => now(),
                        'description' => $description ?: 'Mutasi Syirkah Import',
                    ]);
                    $newTx->created_at = $txDate;
                    $newTx->updated_at = $txDate;
                    $newTx->save();
                }

                $affectedUserIds[$user->id] = true;
            }

            // Recalculate running balances chronologically for all affected users
            foreach (array_keys($affectedUserIds) as $userId) {
                SavingTransactionService::recalculateUserTransactions($userId);
            }
        });
    }
}
