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
        $existingIndexes = collect(Schema::getIndexes('attendances'))->pluck('name')->toArray();
        if (!in_array('idx_attendances_user_date_status', $existingIndexes)) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index(['user_id', 'date', 'status'], 'idx_attendances_user_date_status');
            });
        }

        $existingIndexes = collect(Schema::getIndexes('overtimes'))->pluck('name')->toArray();
        if (!in_array('idx_overtimes_emp_date_status', $existingIndexes)) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->index(['employee_id', 'overtime_date', 'status'], 'idx_overtimes_emp_date_status');
            });
        }

        $existingIndexes = collect(Schema::getIndexes('saving_transactions'))->pluck('name')->toArray();
        if (!in_array('idx_saving_tx_user_savings_type', $existingIndexes)) {
            Schema::table('saving_transactions', function (Blueprint $table) {
                $table->index(['user_id', 'savings_id', 'transaction_type'], 'idx_saving_tx_user_savings_type');
            });
        }

        $existingIndexes = collect(Schema::getIndexes('work_schedules'))->pluck('name')->toArray();
        if (!in_array('idx_work_schedules_user_date', $existingIndexes)) {
            Schema::table('work_schedules', function (Blueprint $table) {
                $table->index(['user_id', 'date'], 'idx_work_schedules_user_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_user_date_status');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropIndex('idx_overtimes_emp_date_status');
        });

        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_saving_tx_user_savings_type');
        });

        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_work_schedules_user_date');
        });
    }
};
