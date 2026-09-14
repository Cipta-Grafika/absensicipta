<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('saving_withdrawals') && !Schema::hasColumn('saving_withdrawals', 'transfer_proof')) {
            Schema::table('saving_withdrawals', function (Blueprint $table) {
                $table->string('transfer_proof')->nullable()->after('rejection_reason');
            });
        }

        if (Schema::hasTable('saving_transactions') && !Schema::hasColumn('saving_transactions', 'transfer_proof')) {
            Schema::table('saving_transactions', function (Blueprint $table) {
                $table->string('transfer_proof')->nullable()->after('rejection_reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('saving_withdrawals') && Schema::hasColumn('saving_withdrawals', 'transfer_proof')) {
            Schema::table('saving_withdrawals', function (Blueprint $table) {
                $table->dropColumn('transfer_proof');
            });
        }

        if (Schema::hasTable('saving_transactions') && Schema::hasColumn('saving_transactions', 'transfer_proof')) {
            Schema::table('saving_transactions', function (Blueprint $table) {
                $table->dropColumn('transfer_proof');
            });
        }
    }
};
