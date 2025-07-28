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
        // Add email notification fields to notification_campaigns table
        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->boolean('send_email')->default(false)->after('send_to_all_faculty');
            $table->string('email_subject')->nullable()->after('send_email');
            $table->text('email_message')->nullable()->after('email_subject');
            $table->integer('email_sent_count')->default(0)->after('failed_count');
            $table->integer('email_failed_count')->default(0)->after('email_sent_count');
        });

        // Add email notification fields to student_notification_campaigns table
        Schema::table('student_notification_campaigns', function (Blueprint $table) {
            $table->boolean('send_email')->default(false)->after('send_to_all_students');
            $table->string('email_subject')->nullable()->after('send_email');
            $table->text('email_message')->nullable()->after('email_subject');
            $table->integer('email_sent_count')->default(0)->after('failed_count');
            $table->integer('email_failed_count')->default(0)->after('email_sent_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->dropColumn(['send_email', 'email_subject', 'email_message', 'email_sent_count', 'email_failed_count']);
        });

        Schema::table('student_notification_campaigns', function (Blueprint $table) {
            $table->dropColumn(['send_email', 'email_subject', 'email_message', 'email_sent_count', 'email_failed_count']);
        });
    }
};
