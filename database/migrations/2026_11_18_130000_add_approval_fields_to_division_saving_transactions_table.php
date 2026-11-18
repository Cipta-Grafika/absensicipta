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
        if (!Schema::hasColumn('division_saving_transactions', 'status')) {
            Schema::table('division_saving_transactions', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('transaction_type');
                $table->decimal('balance_before', 15, 2)->nullable()->default(null)->change();
                $table->decimal('balance_after', 15, 2)->nullable()->default(null)->change();
                $table->foreignUlid('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable()->after('approved_by');
                $table->foreignUlid('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
                $table->text('rejection_note')->nullable()->after('rejected_at');

                $table->index(['division_id', 'status']);
                $table->index(['status', 'transaction_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('division_saving_transactions', 'status')) {
            Schema::table('division_saving_transactions', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropForeign(['rejected_by']);
                $table->dropIndex(['division_id', 'status']);
                $table->dropIndex(['status', 'transaction_date']);
                $table->dropColumn([
                    'status',
                    'approved_by',
                    'approved_at',
                    'rejected_by',
                    'rejected_at',
                    'rejection_note',
                ]);
            });
        }
    }
};

