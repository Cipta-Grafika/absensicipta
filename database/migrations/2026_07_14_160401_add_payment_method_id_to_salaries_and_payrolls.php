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
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->foreignUlid('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            // Because SQLite doesn't easily support dropping columns that aren't at the end or renaming complex schemas without dropping the whole table, 
            // and we're not sure if it's MySQL or SQLite, we'll try to drop the string column and add the new one.
            // If it fails on SQLite, we might need a workaround, but generally Laravel 11 handles it.
            $table->dropColumn('payment_method');
            $table->foreignUlid('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
            $table->string('payment_method')->nullable();
        });

        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};
