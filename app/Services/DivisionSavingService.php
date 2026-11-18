<?php

namespace App\Services;

use App\Models\Division;
use App\Models\DivisionWallet;
use App\Models\DivisionSavingTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class DivisionSavingService
{
    /**
     * Cari dompet berdasarkan judul di divisi bersangkutan, atau buat baru jika belum ada.
     */
    public static function findOrCreateWallet(int $divisionId, string $title, ?User $actor = null): DivisionWallet
    {
        if ($actor && !$actor->isOwner && !$actor->isSuperadmin && !$actor->hasDivisionAccess($divisionId)) {
            throw new \Illuminate\Auth\Access\AuthorizationException("User tidak memiliki akses ke divisi {$divisionId}.");
        }

        $cleanTitle = trim(preg_replace('/\s+/', ' ', $title));
        if (empty($cleanTitle)) {
            throw new InvalidArgumentException('Judul dompet alokasi tidak boleh kosong.');
        }

        return DivisionWallet::firstOrCreate(
            [
                'division_id' => $divisionId,
                'title' => $cleanTitle,
            ],
            [
                'slug' => Str::slug($cleanTitle),
                'current_balance' => 0,
                'is_active' => true,
                'created_by' => $actor?->id,
            ]
        );
    }

    /**
     * Catat transaksi mutasi (Credit/Deposit atau Debit/Pengeluaran).
     * Dibungkus dalam database transaction dengan pessimistic lock.
     */
    public static function recordTransaction(
        int $divisionId,
        string|DivisionWallet $walletOrTitle,
        string $type,
        float $amount,
        string $description,
        User $actor,
        ?string $transactionDate = null,
        ?string $referenceNumber = null,
        ?string $proofAttachmentPath = null
    ): DivisionSavingTransaction {
        if (!$actor->isOwner && !$actor->isSuperadmin && !$actor->hasDivisionAccess($divisionId)) {
            throw new \Illuminate\Auth\Access\AuthorizationException("User tidak memiliki wewenang mencatat transaksi di divisi {$divisionId}.");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal transaksi harus lebih besar dari Rp 0.');
        }

        $type = strtolower(trim($type));
        if (!in_array($type, ['credit', 'debit'])) {
            throw new InvalidArgumentException("Tipe transaksi '{$type}' tidak valid. Harus 'credit' atau 'debit'.");
        }

        return DB::transaction(function () use (
            $divisionId,
            $walletOrTitle,
            $type,
            $amount,
            $description,
            $actor,
            $transactionDate,
            $referenceNumber,
            $proofAttachmentPath
        ) {
            // 1. Resolve Wallet
            if ($walletOrTitle instanceof DivisionWallet) {
                $wallet = $walletOrTitle;
            } else {
                $wallet = self::findOrCreateWallet($divisionId, $walletOrTitle, $actor);
            }

            // 2. Lock wallet row untuk mencegah race condition
            $lockedWallet = DivisionWallet::where('id', $wallet->id)
                ->where('division_id', $divisionId)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $lockedWallet->current_balance;

            // 3. Failsafe Overdraft Protection: Pengeluaran (debit) tidak boleh melebihi saldo dompet
            if ($type === 'debit') {
                if ($balanceBefore < $amount) {
                    $saldoFormatted = 'Rp ' . number_format($balanceBefore, 0, ',', '.');
                    throw new RuntimeException("Saldo dompet '{$lockedWallet->title}' tidak mencukupi (Tersedia: {$saldoFormatted}). Transaksi dibatalkan.");
                }
                $balanceAfter = $balanceBefore - $amount;
            } else {
                $balanceAfter = $balanceBefore + $amount;
            }

            // 4. Update Wallet Balance
            $lockedWallet->current_balance = $balanceAfter;
            $lockedWallet->updated_by = $actor->id;
            $lockedWallet->save();

            // 5. Generate Transaction Code: DSV-DIV{id}-YYYYMM-XXXX
            $dateObj = $transactionDate ? Carbon::parse($transactionDate) : now();
            $prefix = 'DSV-' . $divisionId . '-' . $dateObj->format('Ym') . '-';
            $seq = DivisionSavingTransaction::where('transaction_code', 'like', $prefix . '%')->count() + 1;
            $txCode = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            // 6. Buat Record Buku Besar
            return DivisionSavingTransaction::create([
                'transaction_code' => $txCode,
                'division_id' => $divisionId,
                'wallet_id' => $lockedWallet->id,
                'transaction_type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => $dateObj->toDateString(),
                'description' => trim($description),
                'reference_number' => $referenceNumber ? trim($referenceNumber) : null,
                'proof_attachment_path' => $proofAttachmentPath,
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * Self-healing: Rekonsiliasi saldo dompet terhadap riwayat mutasi
     */
    public static function reconcileWallet(string $walletId): void
    {
        DB::transaction(function () use ($walletId) {
            $wallet = DivisionWallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
            $calculatedBalance = (float) DivisionSavingTransaction::where('wallet_id', $walletId)
                ->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as net_balance")
                ->value('net_balance') ?? 0.0;

            $wallet->current_balance = max(0, $calculatedBalance);
            $wallet->save();
        });
    }

    /**
     * Rekap ringkasan keuangan untuk 1 divisi
     */
    public static function getDivisionSummary(int $divisionId, ?string $month = null): array
    {
        $monthDate = $month ? Carbon::parse($month . '-01') : now();
        $startOfMonth = $monthDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $monthDate->copy()->endOfMonth()->toDateString();

        $totalBalance = (float) DivisionWallet::where('division_id', $divisionId)
            ->where('is_active', true)
            ->sum('current_balance');

        $monthTransactions = DivisionSavingTransaction::where('division_id', $divisionId)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->selectRaw("
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit
            ")
            ->first();

        $totalWalletsCount = DivisionWallet::where('division_id', $divisionId)
            ->where('is_active', true)
            ->count();

        return [
            'total_balance' => $totalBalance,
            'total_credit_month' => (float) ($monthTransactions?->total_credit ?? 0),
            'total_debit_month' => (float) ($monthTransactions?->total_debit ?? 0),
            'total_wallets_count' => $totalWalletsCount,
            'period_label' => $monthDate->translatedFormat('F Y'),
        ];
    }

    /**
     * Rekap ringkasan keuangan global untuk seluruh divisi (Khusus Owner / Superadmin)
     */
    public static function getCompanySummary(?string $month = null): array
    {
        $monthDate = $month ? Carbon::parse($month . '-01') : now();
        $startOfMonth = $monthDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $monthDate->copy()->endOfMonth()->toDateString();

        $totalBalance = (float) DivisionWallet::where('is_active', true)->sum('current_balance');

        $monthTransactions = DivisionSavingTransaction::whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->selectRaw("
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit
            ")
            ->first();

        $totalWalletsCount = DivisionWallet::where('is_active', true)->count();
        $totalDivisionsCount = Division::count();

        return [
            'total_balance' => $totalBalance,
            'total_credit_month' => (float) ($monthTransactions?->total_credit ?? 0),
            'total_debit_month' => (float) ($monthTransactions?->total_debit ?? 0),
            'total_wallets_count' => $totalWalletsCount,
            'total_divisions_count' => $totalDivisionsCount,
            'period_label' => $monthDate->translatedFormat('F Y'),
        ];
    }
}
