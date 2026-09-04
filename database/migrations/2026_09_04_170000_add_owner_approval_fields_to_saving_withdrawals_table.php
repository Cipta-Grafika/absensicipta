<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saving_withdrawals', function (Blueprint $table) {
            $table->decimal('approved_mandatory_amount', 15, 2)->nullable()->after('total_amount');
            $table->decimal('approved_secondary_amount', 15, 2)->nullable()->after('approved_mandatory_amount');
            $table->decimal('approved_total_amount', 15, 2)->nullable()->after('approved_secondary_amount');
            $table->foreignUlid('owner_approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('owner_approved_at')->nullable()->after('owner_approved_by');
            $table->text('admin_note')->nullable()->after('reason');
            $table->text('owner_note')->nullable()->after('admin_note');
        });

        // Modify status column enum to include 'approved'
        try {
            DB::statement("ALTER TABLE saving_withdrawals MODIFY COLUMN status ENUM('pending', 'accepted', 'approved', 'rejected', 'paid') DEFAULT 'pending'");
        } catch (\Throwable $e) {
            // Safe-fail if DB driver is SQLite or doesn't support ALTER ENUM directly
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_withdrawals', function (Blueprint $table) {
            $table->dropForeign(['owner_approved_by']);
            $table->dropColumn([
                'approved_mandatory_amount',
                'approved_secondary_amount',
                'approved_total_amount',
                'owner_approved_by',
                'owner_approved_at',
                'admin_note',
                'owner_note',
            ]);
        });

        try {
            DB::statement("ALTER TABLE saving_withdrawals MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'paid') DEFAULT 'pending'");
        } catch (\Throwable $e) {
            // Safe-fail
        }
    }
};
