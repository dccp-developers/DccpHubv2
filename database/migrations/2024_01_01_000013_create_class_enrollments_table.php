<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('class_enrollments')) {

            Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->string('student_id');
            $table->datetime('completion_date')->nullable();
            $table->boolean('status')->default(true);
            $table->text('remarks')->nullable();
            $table->decimal('prelim_grade', 5, 2)->nullable();
            $table->decimal('midterm_grade', 5, 2)->nullable();
            $table->decimal('finals_grade', 5, 2)->nullable();
            $table->decimal('total_average', 5, 2)->nullable();
            $table->boolean('is_grades_finalized')->default(false);
            $table->boolean('is_grades_verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes');
        });

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollments');
    }
};
