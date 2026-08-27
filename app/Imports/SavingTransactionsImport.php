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
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;

class SavingTransactionsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $rows)
    {
        $affectedUserIds = [];

        DB::transaction(function () use ($rows, &$affectedUserIds) {
            $defaultSaving = Saving::first();

            foreach ($rows as $row) {
                $nip = trim($row['nip'] ?? '');
                $empName = trim($row['nama_karyawan'] ?? '');
                $savingName = trim($row['nama_syirkah'] ?? '');
                $rawDate = trim($row['tanggal'] ?? '');
                $rawType = strtolower(trim($row['tipe_transaksi'] ?? 'deposit'));
                $mandatory = (float) ($row['mutasi_wajib'] ?? 0);
                $secondary = (float) ($row['mutasi_sukarela'] ?? 0);
                $description = trim($row['keterangan'] ?? '');

                if (empty($nip) && empty($empName)) {
                    continue;
                }

                // Match User
                $user = null;
                if (!empty($nip)) {
                    $user = User::where('nip', $nip)->first();
                }
                if (!$user && !empty($empName)) {
                    $user = User::where('name', 'like', "%{$empName}%")->first();
                }

                if (!$user) {
                    continue;
                }

                // Match Saving Program
                $saving = null;
                if (!empty($savingName)) {
                    $saving = Saving::where('savings_name', 'like', "%{$savingName}%")->first();
                }
                if (!$saving) {
                    $saving = $defaultSaving;
                }

                if (!$saving) {
                    continue;
                }

                // Parse Date
                try {
                    $txDate = Carbon::parse($rawDate);
                } catch (\Throwable $e) {
                    $txDate = now();
                }

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

                // Look for existing identical transaction by user, date, description to overwrite
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
                    $existingTx->update([
                        'transaction_type' => $txType,
                        'mandatory_amount' => $mandatory,
                        'secondary_amount' => $secondary,
                        'status' => 'approved',
                        'approved_by' => auth()->id() ?: $existingTx->approved_by,
                        'approval_date' => $existingTx->approval_date ?: now(),
                        'description' => $description ?: $existingTx->description,
                        'created_at' => $txDate,
                        'updated_at' => now(),
                    ]);
                } else {
                    SavingTransaction::create([
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
                        'created_at' => $txDate,
                        'updated_at' => $txDate,
                    ]);
                }

                $affectedUserIds[$user->id] = true;
            }

            // Recalculate running balances chronologically for all affected users
            foreach (array_keys($affectedUserIds) as $userId) {
                SavingTransactionService::recalculateUserTransactions($userId);
            }
        });
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required'],
        ];
    }
}
