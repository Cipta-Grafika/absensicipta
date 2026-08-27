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
        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('description');
            $table->foreignUlid('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approval_date')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approval_date');

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['status', 'approved_by', 'approval_date', 'rejection_reason']);
        });
    }
};
