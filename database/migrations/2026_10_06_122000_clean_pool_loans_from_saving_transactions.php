<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\LoanService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        LoanService::cleanInvalidPoolTransactions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed for data cleanup
    }
};
