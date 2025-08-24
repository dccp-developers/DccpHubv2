<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('faculty')) {
            Schema::create('faculty', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('faculty_id_number', 20)->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->string('department')->nullable();
            $table->text('office_hours')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address_line1')->nullable();
            $table->text('biography')->nullable();
            $table->text('education')->nullable();
            $table->text('courses_taught')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('status')->default('active');
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty');
    }
};
