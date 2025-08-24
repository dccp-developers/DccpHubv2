<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('middle_name', 20);
            $table->string('gender', 10);
            $table->date('birth_date');
            $table->integer('age');
            $table->string('address', 255);
            $table->text('contacts');
            $table->unsignedBigInteger('course_id');
            $table->integer('academic_year');
            $table->string('email', 255);
            $table->text('remarks')->nullable();
            $table->string('profile_url', 255)->nullable();
            $table->unsignedBigInteger('student_contact_id')->nullable();
            $table->integer('student_parent_info')->nullable();
            $table->integer('student_education_id')->nullable();
            $table->integer('student_personal_id')->nullable();
            $table->integer('document_location_id')->nullable();
            $table->string('student_id')->nullable();
            $table->string('status')->nullable();
            $table->string('clearance_status')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('student_contact_id')->references('id')->on('student_contacts');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
