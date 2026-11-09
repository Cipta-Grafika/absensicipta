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
        Schema::create('transfer_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained('saving_withdrawals')->cascadeOnDelete();
            $table->string('id_pengajuan', 40)->unique();
            $table->string('delivery_status', 30)->default('QUEUED')->index();
            $table->string('id_ledger', 100)->nullable();
            $table->string('status_ledger', 50)->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('first_attempt_at')->nullable();
            $table->timestamp('first_failure_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->string('last_error_code', 50)->nullable();
            $table->text('last_error_message')->nullable();
            $table->json('payload')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_deliveries');
    }
};

