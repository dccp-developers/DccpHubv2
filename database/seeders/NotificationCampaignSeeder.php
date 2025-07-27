<?php

namespace Database\Seeders;

use App\Models\NotificationCampaign;
use App\Models\User;
use App\Enums\NotificationType;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;

use Illuminate\Database\Seeder;

class NotificationCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('role', 'admin')->first();

        if (!$adminUser) {
            $this->command->warn('No admin user found. Skipping notification campaign seeding.');
            return;
        }

        $campaigns = [
            [
                'title' => 'Weekly Schedule Update',
                'description' => 'Notify faculty about weekly schedule changes',
                'type' => NotificationType::SCHEDULE_CHANGE,
                'priority' => NotificationPriority::HIGH,
                'status' => NotificationStatus::DRAFT,
                'notification_title' => 'Your Schedule Has Been Updated',
                'notification_message' => 'Please review your updated class schedule for this week. Some time slots may have changed.',
                'action_text' => 'View Schedule',
                'action_url' => '/faculty/schedule',
                'send_to_all_faculty' => true,
            ],
            [
                'title' => 'Grade Submission Deadline Reminder',
                'description' => 'Remind faculty to submit grades before deadline',
                'type' => NotificationType::GRADE_SUBMISSION_REMINDER,
                'priority' => NotificationPriority::URGENT,
                'status' => NotificationStatus::DRAFT,
                'notification_title' => 'Grade Submission Deadline Approaching',
                'notification_message' => 'Please submit all student grades by Friday, 5:00 PM. Late submissions may affect student records.',
                'action_text' => 'Submit Grades',
                'action_url' => '/faculty/grades',
                'send_to_all_faculty' => true,
                'expires_at' => now()->addDays(7),
            ],
            [
                'title' => 'Faculty Meeting Announcement',
                'description' => 'Announce upcoming faculty meeting',
                'type' => NotificationType::MEETING_ANNOUNCEMENT,
                'priority' => NotificationPriority::NORMAL,
                'status' => NotificationStatus::DRAFT,
                'notification_title' => 'Faculty Meeting - Next Monday',
                'notification_message' => 'Join us for the monthly faculty meeting next Monday at 2:00 PM in the conference room.',
                'action_text' => 'View Details',
                'action_url' => '/faculty/meetings',
                'send_to_all_faculty' => true,
                'scheduled_at' => now()->addDays(2),
            ],
            [
                'title' => 'System Maintenance Notice',
                'description' => 'Inform about scheduled system maintenance',
                'type' => NotificationType::SYSTEM_MAINTENANCE,
                'priority' => NotificationPriority::URGENT,
                'status' => NotificationStatus::SENT,
                'notification_title' => 'Scheduled System Maintenance',
                'notification_message' => 'The system will be down for maintenance on Saturday from 2:00 AM to 6:00 AM. Please save your work.',
                'action_text' => 'Learn More',
                'action_url' => '/maintenance',
                'send_to_all_faculty' => true,
                'sent_at' => now()->subDays(1),
                'total_recipients' => 25,
                'sent_count' => 24,
                'failed_count' => 1,
            ],
            [
                'title' => 'New Student Enrollment Alert',
                'description' => 'Notify about new student enrollments',
                'type' => NotificationType::STUDENT_ENROLLMENT,
                'priority' => NotificationPriority::NORMAL,
                'status' => NotificationStatus::SENT,
                'notification_title' => 'New Students Enrolled',
                'notification_message' => 'Several new students have enrolled in your classes. Please review the updated class rosters.',
                'action_text' => 'View Students',
                'action_url' => '/faculty/students',
                'send_to_all_faculty' => false,
                'sent_at' => now()->subHours(6),
                'total_recipients' => 8,
                'sent_count' => 8,
                'failed_count' => 0,
            ],
        ];

        foreach ($campaigns as $campaignData) {
            $campaignData['created_by'] = $adminUser->id;
            NotificationCampaign::create($campaignData);
        }

        $this->command->info('Notification campaigns seeded successfully!');
    }
}
