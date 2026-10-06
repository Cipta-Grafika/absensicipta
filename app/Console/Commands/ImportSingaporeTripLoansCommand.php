<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Services\SavingTransactionService;
use Illuminate\Support\Facades\DB;

class ImportSingaporeTripLoansCommand extends Command
{
    protected $signature = 'trip:import-singapore 
                            {--source=sukarela : Source of talangan: "sukarela" (default) or "wajib"}
                            {--destination=sukarela : Repayment target: "sukarela" (default) or "wajib"}
                            {--auto-approve : Automatically approve the imported loans}';
    protected $description = 'Import 15 participants from Rekap Biaya Trip Singapore into Loans and Syirkah system';

    public function handle()
    {
        $sourceOpt = strtolower($this->option('source'));
        $destOpt = strtolower($this->option('destination'));

        $disbursementSource = ($sourceOpt === 'wajib') ? 'syirkah_pool_mandatory' : 'syirkah_pool_secondary';
        $syirkahDestination = ($destOpt === 'wajib') ? 'syirkah_pool_mandatory' : 'syirkah_pool_secondary';

        $this->info("Starting Import of Rekap Biaya Trip Singapore (15 Orang)...");
        $this->info("Sumber Pencairan: " . ($disbursementSource === 'syirkah_pool_mandatory' ? 'Kas Talangan Syirkah Wajib' : 'Kas Talangan Syirkah Sukarela (SSR)'));
        $this->info("Target Pengembalian: " . ($syirkahDestination === 'syirkah_pool_mandatory' ? 'Kas Talangan Syirkah Wajib' : 'Kas Talangan Syirkah Sukarela (SSR)'));

        $participants = [
            ['name' => 'Rangga Jatnika', 'division' => 'Graha', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Maolinda Mugni', 'division' => 'Purwakarta', 'amount' => 3120000, 'tenor' => 3, 'installment' => 1040000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026 (Tenor 3 bln)'],
            ['name' => 'Zaky Ahmad', 'division' => 'Corporate', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Amelia Azhara Crisnandi', 'division' => 'Corporate', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Nayla Reva', 'division' => 'CG', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Avrilriani Magdalena', 'division' => 'Corporate', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Ramdani', 'division' => 'CG', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Rahmanda Andita', 'division' => 'Graha', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Aziz Jabbar Shidiqie', 'division' => 'Purwakarta', 'amount' => 3120000, 'tenor' => 4, 'installment' => 780000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026 (Tenor 4 bln)'],
            ['name' => 'Andre', 'division' => 'Corporate', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Zaenal Alfian', 'division' => 'Corporate', 'amount' => 3120000, 'tenor' => 3, 'installment' => 1040000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026 (Tenor 3 bln)'],
            ['name' => 'Dhea Amanda', 'division' => 'Corporate', 'amount' => 3120000, 'tenor' => 3, 'installment' => 1040000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026 (Tenor 3 bln)'],
            ['name' => 'Dini Aulia Andien', 'division' => 'Online', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Muhammad Angga', 'division' => 'CG', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
            ['name' => 'Dandan Rolis Harnika', 'division' => 'Corporate', 'amount' => 3120000, 'tenor' => 6, 'installment' => 520000, 'method' => 'payroll', 'disbursement' => $disbursementSource, 'destination' => $syirkahDestination, 'note' => 'Program Trip Singapore 2026'],
        ];

        $autoApprove = $this->option('auto-approve');
        $createdCount = 0;

        foreach ($participants as $p) {
            $user = User::where('name', 'like', '%' . $p['name'] . '%')->first();

            if (!$user) {
                // In case user does not exist in local db, find by first word or create stub if desired
                $firstWord = explode(' ', $p['name'])[0];
                $user = User::where('name', 'like', '%' . $firstWord . '%')->first();
            }

            if (!$user) {
                $this->warn("User [{$p['name']}] not found in database. Skipping...");
                continue;
            }

            // Check if loan already exists
            $existing = Loan::where('user_id', $user->id)
                ->where('description', 'like', '%Trip Singapore%')
                ->first();

            if ($existing) {
                $this->line("Loan already exists for [{$user->name}] - Status: {$existing->status}. Skipping.");
                continue;
            }

            DB::transaction(function () use ($user, $p, $autoApprove, &$createdCount) {
                $loan = Loan::create([
                    'user_id' => $user->id,
                    'loan_amount' => $p['amount'],
                    'tenor_months' => $p['tenor'],
                    'installment_amount' => $p['installment'],
                    'remaining_balance' => $p['amount'],
                    'payment_source' => $p['method'],
                    'disbursement_source' => $p['disbursement'],
                    'syirkah_destination' => $p['destination'],
                    'status' => $autoApprove ? 'active' : 'pending',
                    'approved_by' => $autoApprove ? User::where('group', 'superadmin')->orWhere('group', 'payroll')->value('id') : null,
                    'approval_date' => $autoApprove ? now() : null,
                    'description' => $p['note'],
                ]);

                $createdCount++;
                $this->info("Created Loan for [{$user->name}] - Plafon: Rp " . number_format($p['amount']) . " - Tenor: {$p['tenor']} Bln");

                \App\Services\LoanService::syncLoan($loan);
            });
        }

        $this->info("Finished! Total {$createdCount} loans processed successfully.");
    }
}
