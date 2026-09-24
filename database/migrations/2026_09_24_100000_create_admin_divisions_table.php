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
        Schema::create('admin_divisions', function (Blueprint $table) {
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'division_id']);
        });

        // Migrate existing admin division data to admin_divisions table
        $admins = DB::table('users')
            ->where('group', 'admin')
            ->whereNotNull('division_id')
            ->get();

        foreach ($admins as $admin) {
            DB::table('admin_divisions')->insertOrIgnore([
                'user_id' => $admin->id,
                'division_id' => $admin->division_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_divisions');
    }
};
