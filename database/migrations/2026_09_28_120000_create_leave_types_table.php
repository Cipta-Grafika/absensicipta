<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('default_days')->default(1);
            $table->boolean('deducts_annual_quota')->default(false);
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default standard leave types
        DB::table('leave_types')->insert([
            [
                'code' => 'ANNUAL',
                'name' => 'Cuti Tahunan',
                'default_days' => 12,
                'deducts_annual_quota' => true,
                'requires_attachment' => false,
                'is_active' => true,
                'description' => 'Cuti tahunan reguler yang mengurangi kuota cuti tahunan karyawan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MARRIAGE',
                'name' => 'Cuti Menikah',
                'default_days' => 3,
                'deducts_annual_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'description' => 'Cuti khusus pernikahan karyawan (3 hari kerja).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CHILD_MARRIAGE',
                'name' => 'Cuti Menikahkan Anak',
                'default_days' => 2,
                'deducts_annual_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'description' => 'Cuti khusus menikahkan anak kandung (2 hari kerja).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MATERNITY',
                'name' => 'Cuti Melahirkan',
                'default_days' => 90,
                'deducts_annual_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'description' => 'Cuti melahirkan bagi karyawan wanita (3 bulan / 90 hari kalender).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PATERNITY',
                'name' => 'Cuti Istri Melahirkan / Keguguran',
                'default_days' => 2,
                'deducts_annual_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'description' => 'Cuti bagi karyawan pria saat istri melahirkan atau keguguran (2 hari kerja).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BEREAVEMENT_PRIMARY',
                'name' => 'Cuti Duka Cita (Keluarga Inti)',
                'default_days' => 2,
                'deducts_annual_quota' => false,
                'requires_attachment' => false,
                'is_active' => true,
                'description' => 'Cuti duka cita bila suami/istri, orang tua/mertua, atau anak meninggal dunia (2 hari kerja).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BEREAVEMENT_SECONDARY',
                'name' => 'Cuti Duka Cita (Anggota Serumah)',
                'default_days' => 1,
                'deducts_annual_quota' => false,
                'requires_attachment' => false,
                'is_active' => true,
                'description' => 'Cuti duka cita bila anggota keluarga dalam satu rumah meninggal dunia (1 hari kerja).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CIRCUMCISION_BAPTISM',
                'name' => 'Cuti Khitanan / Baptis Anak',
                'default_days' => 2,
                'deducts_annual_quota' => false,
                'requires_attachment' => false,
                'is_active' => true,
                'description' => 'Cuti khusus khitanan atau pembaptisan anak kandung (2 hari kerja).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'HAJJ_UMRAH',
                'name' => 'Cuti Ibadah Haji / Umroh',
                'default_days' => 40,
                'deducts_annual_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'description' => 'Cuti menunaikan kewajiban ibadah keagamaan haji/umroh yang pertama kali.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SPECIAL',
                'name' => 'Cuti Khusus Lainnya',
                'default_days' => 1,
                'deducts_annual_quota' => false,
                'requires_attachment' => false,
                'is_active' => true,
                'description' => 'Cuti khusus dengan persetujuan manajemen perusahaan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
