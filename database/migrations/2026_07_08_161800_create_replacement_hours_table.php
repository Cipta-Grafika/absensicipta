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
        Schema::create('replacement_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->date('replaced_date'); // Tanggal absensi yang bermasalah (telat/izin/absen)
            $table->date('replacement_date'); // Tanggal penggantian jam
            $table->time('start_hour');
            $table->time('end_hour');
            $table->text('reason');
            $table->string('attachment')->nullable(); // lampiran bukti
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replacement_hours');
    }
};
