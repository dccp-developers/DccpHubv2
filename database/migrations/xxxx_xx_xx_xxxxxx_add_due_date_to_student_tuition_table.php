<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_tuition', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('status'); // Add after 'status'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_tuition', function (Blueprint $table): void {
            $table->dropColumn('due_date');
        });
    }
};
