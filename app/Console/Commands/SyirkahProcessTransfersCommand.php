<?php

namespace App\Console\Commands;

use App\Models\SavingWithdrawal;
use App\Services\SyirkahTransferService;
use Illuminate\Console\Command;

class SyirkahProcessTransfersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syirkah:process-transfers
                            {--test : Mode pengujian: kirim nominal Rp 1.000 dengan catatan TESTING SAJA}
                            {--retry-only : Hanya proses antrean pengiriman ulang yang sudah jatuh tempo}
                            {--queue-only : Hanya proses antrean pencairan syirkah approved yang belum terkirim}
                            {--id= : ID pengajuan penarikan syirkah (SavingWithdrawal ID) yang ingin diproses}
                            {--nominal= : Nominal kustom dalam rupiah (contoh: 1000)}
                            {--keperluan= : Catatan keperluan kustom}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim antrean pembayaran syirkah yang telah diapprove Owner secara berurutan ke SYIRKAH_TRANSFER_URL Pak Fathi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isTest = $this->option('test');
        $retryOnly = $this->option('retry-only');
        $queueOnly = $this->option('queue-only');
        $specificId = $this->option('id');
        $customNominal = $this->option('nominal') ? (int) $this->option('nominal') : null;
        $customKeperluan = $this->option('keperluan');

        $this->info("=== Integrasi Transfer Syirkah -> Bot Transfer Pak Fathi ===");
        $url = config('services.syirkah.transfer_url', env('SYIRKAH_TRANSFER_URL'));
        $this->line("Target URL : {$url}");
        $this->line("Mode       : " . ($isTest ? "TESTING (Nominal: Rp 1.000)" : "PRODUCTION (Nominal Riil)"));
        $this->newLine();

        // 1. Process specific withdrawal by ID
        if ($specificId) {
            $withdrawal = SavingWithdrawal::with(['user.paymentMethod', 'ownerApprover'])->find($specificId);
            if (!$withdrawal) {
                $this->error("Pengajuan dengan ID '{$specificId}' tidak ditemukan.");
                return Command::FAILURE;
            }

            $this->info("Memproses pengajuan: {$withdrawal->id} ({$withdrawal->user?->name})...");
            $delivery = SyirkahTransferService::send($withdrawal, $isTest, $customNominal, $customKeperluan);

            if ($delivery->isSuccessful()) {
                $this->info("✅ Berhasil dikirim ke Bendahara! Status: {$delivery->delivery_status}, Ledger ID: {$delivery->id_ledger}");
                return Command::SUCCESS;
            }

            $this->warn("⚠️ Status pengiriman: {$delivery->delivery_status} (HTTP {$delivery->last_http_status})");
            if ($delivery->last_error_message) {
                $this->line("Pesan: {$delivery->last_error_message}");
            }
            if ($delivery->next_retry_at) {
                $this->line("Jadwal retry berikutnya: {$delivery->next_retry_at->toDateTimeString()}");
            }

            return Command::SUCCESS;
        }

        // 2. Retry pending deliveries
        if (!$queueOnly) {
            $this->info("Memeriksa antrean retry yang sudah jatuh tempo...");
            $retryResults = SyirkahTransferService::retryPendingDeliveries();
            $this->line("Due: {$retryResults['total_due']}, Diproses: {$retryResults['processed']}, Sukses: {$retryResults['success']}, Gagal/Retry: {$retryResults['failed']}");
            $this->newLine();
        }

        // 3. Process approved payment queue
        if (!$retryOnly) {
            $this->info("Memeriksa antrean pembayaran syirkah berstatus APPROVED (Owner)...");
            $queueResults = SyirkahTransferService::processPaymentQueue($isTest);
            $this->line("Ditemukan: {$queueResults['total_found']}, Diproses: {$queueResults['processed']}, Sukses: {$queueResults['success']}, Gagal/Retry: {$queueResults['failed']}");

            if (!empty($queueResults['details'])) {
                $this->table(
                    ['ID Pengajuan', 'Application ID', 'Status', 'HTTP', 'Keterangan'],
                    array_map(fn($d) => [
                        $d['id_pengajuan'],
                        $d['application_id'],
                        $d['status'],
                        $d['http_status'] ?: '-',
                        substr((string) ($d['error'] ?? '-'), 0, 50),
                    ], $queueResults['details'])
                );
            }
        }

        $this->newLine();
        $this->info("Selesai memproses antrean transfer.");
        return Command::SUCCESS;
    }
}

