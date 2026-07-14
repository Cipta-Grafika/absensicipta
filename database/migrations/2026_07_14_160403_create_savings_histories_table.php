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
        Schema::create('savings_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUlid('savings_id')->constrained('savings')->onDelete('cascade');
            $table->decimal('mandatory_savings', 15, 2)->default(0);
            $table->decimal('secondary_savings', 15, 2)->default(0);
            $table->decimal('total_mandatory', 15, 2)->default(0);
            $table->decimal('total_secondary', 15, 2)->default(0);
            $table->decimal('total_savings', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_histories');
    }
};
