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
        Schema::create('student_notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // NotificationType enum
            $table->string('priority')->default('normal'); // NotificationPriority enum
            $table->string('status')->default('draft'); // NotificationStatus enum

            // Notification content
            $table->string('notification_title');
            $table->text('notification_message');
            $table->string('action_text')->nullable();
            $table->string('action_url')->nullable();
            $table->json('additional_data')->nullable();

            // Recipients
            $table->json('recipient_ids')->nullable(); // Array of user IDs
            $table->boolean('send_to_all_students')->default(false);
            $table->json('recipient_filters')->nullable(); // Filters for dynamic recipients

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Tracking
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('error_log')->nullable();

            // Audit
            $table->foreignId('created_by')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('accounts')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['type', 'status']);
            $table->index(['scheduled_at']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_notification_campaigns');
    }
};
