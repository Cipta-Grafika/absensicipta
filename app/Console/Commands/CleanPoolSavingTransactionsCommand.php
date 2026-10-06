<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LoanService;

class CleanPoolSavingTransactionsCommand extends Command
{
    protected $signature = 'syirkah:clean-pool';
    protected $description = 'Clean all invalid pool loan disbursement/installment transactions from personal savings ledger and recalculate balances';

    public function handle()
    {
        $this->info('Memulai pembersihan mutasi kas talangan/pool dari buku tabungan pribadi karyawan...');
        
        LoanService::cleanInvalidPoolTransactions();
        LoanService::syncAllLoans();

        $this->info('Pembersihan berhasil! Buku tabungan syirkah pribadi seluruh karyawan telah disinkronkan dan transaksi kas talangan telah dipisahkan ke pembukuan kas pool.');
        return Command::SUCCESS;
    }
}
