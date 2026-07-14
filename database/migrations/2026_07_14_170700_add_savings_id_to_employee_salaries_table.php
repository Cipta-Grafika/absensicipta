<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->foreignUlid('savings_id')->nullable()->constrained('savings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->dropForeign(['savings_id']);
            $table->dropColumn('savings_id');
        });
    }
};
