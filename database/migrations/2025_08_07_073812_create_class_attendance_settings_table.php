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
        Schema::create('class_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->uuid('faculty_id');

            // Attendance tracking settings
            $table->boolean('is_enabled')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Attendance method and policy
            $table->string('attendance_method')->default('manual'); // manual, qr_code, geolocation, etc.
            $table->string('attendance_policy')->default('present_by_default'); // present_by_default, absent_by_default, require_check_in

            // Timing settings
            $table->integer('grace_period_minutes')->default(15); // How many minutes late is still "present"
            $table->integer('auto_mark_absent_minutes')->nullable(); // Auto mark absent after X minutes
            $table->boolean('allow_late_checkin')->default(true);
            $table->time('checkin_start_time')->nullable(); // When students can start checking in
            $table->time('checkin_end_time')->nullable(); // When check-in window closes

            // QR Code settings
            $table->string('qr_code_token')->nullable();
            $table->timestamp('qr_code_expires_at')->nullable();
            $table->boolean('qr_code_auto_refresh')->default(true);
            $table->integer('qr_code_refresh_minutes')->default(30);

            // Attendance Code settings
            $table->string('attendance_code')->nullable(); // Simple code for students to enter
            $table->timestamp('attendance_code_expires_at')->nullable();
            $table->boolean('attendance_code_auto_refresh')->default(false);

            // Self check-in settings
            $table->boolean('require_confirmation')->default(false); // Require teacher confirmation for self check-ins
            $table->boolean('show_class_list')->default(true); // Show other students who checked in

            // Notification settings
            $table->boolean('notify_absent_students')->default(false);
            $table->boolean('notify_late_students')->default(false);
            $table->boolean('send_daily_summary')->default(false);

            // Additional settings
            $table->json('additional_settings')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('faculty_id')->references('id')->on('faculty')->onDelete('cascade');

            // Indexes
            $table->unique(['class_id']); // One setting per class
            $table->index(['faculty_id']);
            $table->index(['is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_attendance_settings');
    }
};
