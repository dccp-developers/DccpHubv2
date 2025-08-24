<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {

            Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->unsignedBigInteger('class_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'partial'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('location_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('marked_by')->references('id')->on('accounts');

            // Indexes for performance
            $table->index(['class_id', 'date'], 'idx_attendances_class_date');
            $table->index(['student_id', 'date'], 'idx_attendances_student_date');
            $table->index(['date', 'status'], 'idx_attendances_date_status');
        });

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
