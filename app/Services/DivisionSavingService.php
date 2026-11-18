<?php

namespace App\Services;

use App\Models\Division;
use App\Models\DivisionWallet;
use App\Models\DivisionSavingTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
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
            throw new AuthorizationException("User tidak memiliki akses ke divisi {$divisionId}.");
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
     * ADMIN: Otomatis masuk sebagai 'pending' (pengajuan) tanpa langsung memotong/menambah saldo.
     * OWNER/SUPERADMIN: Bisa langsung disetujui ('approved') atau dibuat sebagai 'pending'.
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
        ?string $proofAttachmentPath = null,
        ?bool $autoApprove = null
    ): DivisionSavingTransaction {
        if (!$actor->isOwner && !$actor->isSuperadmin && !$actor->hasDivisionAccess($divisionId)) {
            throw new AuthorizationException("User tidak memiliki wewenang mencatat transaksi di divisi {$divisionId}.");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal transaksi harus lebih besar dari Rp 0.');
        }

        $type = strtolower(trim($type));
        if (!in_array($type, ['credit', 'debit'])) {
            throw new InvalidArgumentException("Tipe transaksi '{$type}' tidak valid. Harus 'credit' atau 'debit'.");
        }

        // Resolusi status awal: Admin selalu pending, Owner/Superadmin default approved
        if ($autoApprove === null) {
            $isApproved = $actor->isOwner || $actor->isSuperadmin;
        } else {
            if ($autoApprove && !$actor->isOwner && !$actor->isSuperadmin) {
                throw new AuthorizationException('Hanya Owner atau Superadmin yang dapat langsung menyetujui transaksi.');
            }
            $isApproved = $autoApprove;
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
            $proofAttachmentPath,
            $isApproved
        ) {
            // 1. Resolve Wallet
            if ($walletOrTitle instanceof DivisionWallet) {
                $wallet = $walletOrTitle;
            } else {
                $wallet = self::findOrCreateWallet($divisionId, $walletOrTitle, $actor);
            }

            // 2. Lock wallet row untuk konsistensi data
            $lockedWallet = DivisionWallet::where('id', $wallet->id)
                ->where('division_id', $divisionId)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = null;
            $balanceAfter = null;

            // Jika langsung disetujui (oleh Owner)
            if ($isApproved) {
                $balanceBefore = (float) $lockedWallet->current_balance;

                if ($type === 'debit') {
                    if ($balanceBefore < $amount) {
                        $saldoFormatted = 'Rp ' . number_format($balanceBefore, 0, ',', '.');
                        throw new RuntimeException("Saldo dompet '{$lockedWallet->title}' tidak mencukupi (Tersedia: {$saldoFormatted}). Transaksi dibatalkan.");
                    }
                    $balanceAfter = $balanceBefore - $amount;
                } else {
                    $balanceAfter = $balanceBefore + $amount;
                }

                $lockedWallet->current_balance = $balanceAfter;
                $lockedWallet->updated_by = $actor->id;
                $lockedWallet->save();
            }

            // 3. Generate Transaction Code: DSV-DIV{id}-YYYYMM-XXXX
            $dateObj = $transactionDate ? Carbon::parse($transactionDate) : now();
            $prefix = 'DSV-' . $divisionId . '-' . $dateObj->format('Ym') . '-';
            $seq = DivisionSavingTransaction::where('transaction_code', 'like', $prefix . '%')->count() + 1;
            $txCode = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            // 4. Buat Record Buku Besar
            return DivisionSavingTransaction::create([
                'transaction_code' => $txCode,
                'division_id' => $divisionId,
                'wallet_id' => $lockedWallet->id,
                'transaction_type' => $type,
                'status' => $isApproved ? 'approved' : 'pending',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => $dateObj->toDateString(),
                'description' => trim($description),
                'reference_number' => $referenceNumber ? trim($referenceNumber) : null,
                'proof_attachment_path' => $proofAttachmentPath,
                'created_by' => $actor->id,
                'approved_by' => $isApproved ? $actor->id : null,
                'approved_at' => $isApproved ? Carbon::now() : null,
            ]);
        });
    }

    /**
     * Persetujuan transaksi oleh OWNER / SUPERADMIN.
     * Mengunci saldo dompet secara pesimistis dan menerapkan kalkulasi mutasi riil.
     */
    public static function approveTransaction(string|DivisionSavingTransaction $transaction, User $approver): DivisionSavingTransaction
    {
        if (!$approver->isOwner && !$approver->isSuperadmin) {
            throw new AuthorizationException('Hanya Owner atau Superadmin yang berwenang menyetujui transaksi tabungan divisi.');
        }

        $txId = $transaction instanceof DivisionSavingTransaction ? $transaction->id : $transaction;

        return DB::transaction(function () use ($txId, $approver) {
            $tx = DivisionSavingTransaction::where('id', $txId)->lockForUpdate()->firstOrFail();

            if ($tx->status !== 'pending') {
                throw new RuntimeException("Transaksi {$tx->transaction_code} tidak dapat disetujui karena statusnya sudah '{$tx->status}'.");
            }

            $wallet = DivisionWallet::where('id', $tx->wallet_id)
                ->where('division_id', $tx->division_id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $wallet->current_balance;

            if ($tx->transaction_type === 'debit') {
                if ($balanceBefore < (float) $tx->amount) {
                    $saldoFormatted = 'Rp ' . number_format($balanceBefore, 0, ',', '.');
                    $butuhFormatted = 'Rp ' . number_format((float) $tx->amount, 0, ',', '.');
                    throw new RuntimeException("Saldo dompet '{$wallet->title}' tidak mencukupi untuk menyetujui pengeluaran ini. (Tersedia: {$saldoFormatted}, Dibutuhkan: {$butuhFormatted}).");
                }
                $balanceAfter = $balanceBefore - (float) $tx->amount;
            } else {
                $balanceAfter = $balanceBefore + (float) $tx->amount;
            }

            $wallet->current_balance = $balanceAfter;
            $wallet->updated_by = $approver->id;
            $wallet->save();

            $tx->status = 'approved';
            $tx->balance_before = $balanceBefore;
            $tx->balance_after = $balanceAfter;
            $tx->approved_by = $approver->id;
            $tx->approved_at = Carbon::now();
            $tx->save();

            return $tx;
        });
    }

    /**
     * Penolakan transaksi oleh OWNER / SUPERADMIN.
     * Saldo dompet tidak berubah sama sekali.
     */
    public static function rejectTransaction(
        string|DivisionSavingTransaction $transaction,
        User $rejector,
        ?string $rejectionNote = null
    ): DivisionSavingTransaction {
        if (!$rejector->isOwner && !$rejector->isSuperadmin) {
            throw new AuthorizationException('Hanya Owner atau Superadmin yang berwenang menolak transaksi tabungan divisi.');
        }

        $txId = $transaction instanceof DivisionSavingTransaction ? $transaction->id : $transaction;

        return DB::transaction(function () use ($txId, $rejector, $rejectionNote) {
            $tx = DivisionSavingTransaction::where('id', $txId)->lockForUpdate()->firstOrFail();

            if ($tx->status !== 'pending') {
                throw new RuntimeException("Transaksi {$tx->transaction_code} tidak dapat ditolak karena statusnya sudah '{$tx->status}'.");
            }

            $tx->status = 'rejected';
            $tx->rejected_by = $rejector->id;
            $tx->rejected_at = Carbon::now();
            $tx->rejection_note = $rejectionNote ? trim($rejectionNote) : null;
            $tx->save();

            return $tx;
        });
    }

    /**
     * Pembatalan pengajuan pending oleh pembuatnya (Admin) atau Owner sebelum disetujui.
     */
    public static function cancelTransaction(string|DivisionSavingTransaction $transaction, User $actor): void
    {
        $txId = $transaction instanceof DivisionSavingTransaction ? $transaction->id : $transaction;

        DB::transaction(function () use ($txId, $actor) {
            $tx = DivisionSavingTransaction::where('id', $txId)->lockForUpdate()->firstOrFail();

            if ($tx->status !== 'pending') {
                throw new RuntimeException("Transaksi {$tx->transaction_code} tidak dapat dibatalkan karena statusnya sudah '{$tx->status}'.");
            }

            if ($tx->created_by !== $actor->id && !$actor->isOwner && !$actor->isSuperadmin) {
                throw new AuthorizationException('Anda tidak memiliki wewenang untuk membatalkan pengajuan ini.');
            }

            $tx->delete();
        });
    }

    /**
     * Self-healing: Rekonsiliasi saldo dompet terhadap riwayat mutasi yang berstatus 'approved'
     */
    public static function reconcileWallet(string $walletId): void
    {
        DB::transaction(function () use ($walletId) {
            $wallet = DivisionWallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
            $calculatedBalance = (float) DivisionSavingTransaction::where('wallet_id', $walletId)
                ->where('status', 'approved')
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
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->selectRaw("
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit
            ")
            ->first();

        $pendingStats = DivisionSavingTransaction::where('division_id', $divisionId)
            ->where('status', 'pending')
            ->selectRaw("
                COUNT(*) as pending_count,
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as pending_credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as pending_debit
            ")
            ->first();

        $totalWalletsCount = DivisionWallet::where('division_id', $divisionId)
            ->where('is_active', true)
            ->count();

        return [
            'total_balance' => $totalBalance,
            'total_credit_month' => (float) ($monthTransactions?->total_credit ?? 0),
            'total_debit_month' => (float) ($monthTransactions?->total_debit ?? 0),
            'pending_count' => (int) ($pendingStats?->pending_count ?? 0),
            'pending_credit' => (float) ($pendingStats?->pending_credit ?? 0),
            'pending_debit' => (float) ($pendingStats?->pending_debit ?? 0),
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

        $monthTransactions = DivisionSavingTransaction::where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->selectRaw("
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit
            ")
            ->first();

        $pendingStats = DivisionSavingTransaction::where('status', 'pending')
            ->selectRaw("
                COUNT(*) as pending_count,
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as pending_credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as pending_debit
            ")
            ->first();

        $totalWalletsCount = DivisionWallet::where('is_active', true)->count();
        $totalDivisionsCount = Division::count();

        return [
            'total_balance' => $totalBalance,
            'total_credit_month' => (float) ($monthTransactions?->total_credit ?? 0),
            'total_debit_month' => (float) ($monthTransactions?->total_debit ?? 0),
            'pending_count' => (int) ($pendingStats?->pending_count ?? 0),
            'pending_credit' => (float) ($pendingStats?->pending_credit ?? 0),
            'pending_debit' => (float) ($pendingStats?->pending_debit ?? 0),
            'total_wallets_count' => $totalWalletsCount,
            'total_divisions_count' => $totalDivisionsCount,
            'period_label' => $monthDate->translatedFormat('F Y'),
        ];
    }
}
