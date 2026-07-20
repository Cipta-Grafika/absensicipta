<?php

// script to manually sync all saving summaries for existing users and their savings

require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SavingTransaction;
use App\Models\SavingSummary;

$transactions = SavingTransaction::select('user_id', 'savings_id')->distinct()->get();
$count = 0;

foreach ($transactions as $t) {
    SavingTransaction::syncSummary($t);
    $count++;
}

echo "Berhasil menyinkronkan $count kombinasi entitas ke dalam tabel saving_summaries.\n";
