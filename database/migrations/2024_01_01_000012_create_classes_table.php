<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('classes')) {

            Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_code')->nullable();
            $table->uuid('faculty_id')->nullable();
            $table->string('academic_year')->nullable();
            $table->string('semester')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('school_year')->nullable();
            $table->json('course_codes')->nullable();
            $table->string('section')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('classification')->nullable();
            $table->integer('maximum_slots')->nullable();
            $table->unsignedBigInteger('shs_track_id')->nullable();
            $table->unsignedBigInteger('shs_strand_id')->nullable();
            $table->string('grade_level')->nullable();
            $table->timestamps();

            $table->foreign('faculty_id')->references('id')->on('faculty');
        });

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
