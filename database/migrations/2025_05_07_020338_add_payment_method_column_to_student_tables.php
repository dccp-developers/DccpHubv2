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
        // Add payment_method column to student_enrollment table
        Schema::table('student_enrollment', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('downpayment');
        });

        // Add payment_method column to student_tuition table
        Schema::table('student_tuition', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('downpayment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove payment_method column from student_enrollment table
        Schema::table('student_enrollment', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        // Remove payment_method column from student_tuition table
        Schema::table('student_tuition', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
