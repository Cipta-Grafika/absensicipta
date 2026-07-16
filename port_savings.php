<?php
use App\Models\SavingsHistory;
use App\Models\SavingTransaction;

$histories = SavingsHistory::orderBy('created_at', 'asc')->get();
$count = 0;

foreach ($histories as $history) {
    // Determine balance before this transaction
    $lastTransaction = SavingTransaction::where('user_id', $history->user_id)
        ->orderBy('created_at', 'desc')
        ->first();
        
    $balanceMandatory = $lastTransaction ? $lastTransaction->balance_mandatory : 0;
    $balanceSecondary = $lastTransaction ? $lastTransaction->balance_secondary : 0;
    
    $newBalanceMandatory = $balanceMandatory + $history->mandatory_savings;
    $newBalanceSecondary = $balanceSecondary + $history->secondary_savings;
    
    SavingTransaction::create([
        'user_id' => $history->user_id,
        'savings_id' => $history->savings_id,
        'transaction_type' => 'deposit',
        'mandatory_amount' => $history->mandatory_savings,
        'secondary_amount' => $history->secondary_savings,
        'balance_mandatory' => $newBalanceMandatory,
        'balance_secondary' => $newBalanceSecondary,
        'description' => 'Migrasi dari histori lama',
        'created_at' => $history->created_at,
        'updated_at' => $history->updated_at,
    ]);
    $count++;
}
echo "Migrated $count records.\n";
