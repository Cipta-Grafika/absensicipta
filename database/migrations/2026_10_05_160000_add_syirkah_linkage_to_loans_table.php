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
        Schema::table('loans', function (Blueprint $table) {
            $table->string('disbursement_source')->default('none')->after('payment_source');
            $table->string('syirkah_destination')->default('none')->after('disbursement_source');
            $table->foreignUlid('saving_transaction_id')->nullable()->after('syirkah_destination')->constrained('saving_transactions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['saving_transaction_id']);
            $table->dropColumn(['disbursement_source', 'syirkah_destination', 'saving_transaction_id']);
        });
    }
};
