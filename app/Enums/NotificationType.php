<?php

namespace App\Enums;

enum NotificationType: string
{
    // Faculty notifications
    case SCHEDULE_CHANGE = 'schedule_change';
    case CLASS_CANCELLATION = 'class_cancellation';
    case STUDENT_ENROLLMENT = 'student_enrollment';
    case STUDENT_SECTION_CHANGE = 'student_section_change';
    case GRADE_SUBMISSION_REMINDER = 'grade_submission_reminder';
    case MEETING_ANNOUNCEMENT = 'meeting_announcement';
    case SYSTEM_MAINTENANCE = 'system_maintenance';
    case CURRICULUM_UPDATE = 'curriculum_update';
    case EXAM_SCHEDULE = 'exam_schedule';
    case DOCUMENT_SUBMISSION = 'document_submission';
    case ROUTE_STATUS_CHANGE = 'route_status_change';
    case ROUTE_DISABLED = 'route_disabled';
    case ROUTE_MAINTENANCE = 'route_maintenance';
    case ROUTE_ENABLED = 'route_enabled';

    // Student notifications
    case ENROLLMENT_APPROVED = 'enrollment_approved';
    case ENROLLMENT_REJECTED = 'enrollment_rejected';
    case TUITION_DUE = 'tuition_due';
    case TUITION_OVERDUE = 'tuition_overdue';
    case TUITION_PAYMENT_CONFIRMED = 'tuition_payment_confirmed';
    case GRADE_RELEASED = 'grade_released';
    case SCHEDULE_UPDATED = 'schedule_updated';
    case CLASS_CANCELLED = 'class_cancelled';
    case EXAM_REMINDER = 'exam_reminder';
    case ASSIGNMENT_DUE = 'assignment_due';
    case GENERAL_ANNOUNCEMENT = 'general_announcement';
    case ACADEMIC_WARNING = 'academic_warning';
    case SCHOLARSHIP_NOTIFICATION = 'scholarship_notification';
    case LIBRARY_NOTICE = 'library_notice';

    // Common notifications
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            // Faculty notifications
            self::SCHEDULE_CHANGE => 'Schedule Change',
            self::CLASS_CANCELLATION => 'Class Cancellation',
            self::STUDENT_ENROLLMENT => 'Student Enrollment',
            self::STUDENT_SECTION_CHANGE => 'Student Section Change',
            self::GRADE_SUBMISSION_REMINDER => 'Grade Submission Reminder',
            self::MEETING_ANNOUNCEMENT => 'Meeting Announcement',
            self::SYSTEM_MAINTENANCE => 'System Maintenance',
            self::CURRICULUM_UPDATE => 'Curriculum Update',
            self::EXAM_SCHEDULE => 'Exam Schedule',
            self::DOCUMENT_SUBMISSION => 'Document Submission',
            self::ROUTE_STATUS_CHANGE => 'Route Status Change',
            self::ROUTE_DISABLED => 'Route Disabled',
            self::ROUTE_MAINTENANCE => 'Route Maintenance',
            self::ROUTE_ENABLED => 'Route Enabled',

            // Student notifications
            self::ENROLLMENT_APPROVED => 'Enrollment Approved',
            self::ENROLLMENT_REJECTED => 'Enrollment Rejected',
            self::TUITION_DUE => 'Tuition Due',
            self::TUITION_OVERDUE => 'Tuition Overdue',
            self::TUITION_PAYMENT_CONFIRMED => 'Payment Confirmed',
            self::GRADE_RELEASED => 'Grade Released',
            self::SCHEDULE_UPDATED => 'Schedule Updated',
            self::CLASS_CANCELLED => 'Class Cancelled',
            self::EXAM_REMINDER => 'Exam Reminder',
            self::ASSIGNMENT_DUE => 'Assignment Due',
            self::GENERAL_ANNOUNCEMENT => 'General Announcement',
            self::ACADEMIC_WARNING => 'Academic Warning',
            self::SCHOLARSHIP_NOTIFICATION => 'Scholarship Notification',
            self::LIBRARY_NOTICE => 'Library Notice',

            // Common notifications
            self::CUSTOM => 'Custom Notification',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            // Faculty notifications
            self::SCHEDULE_CHANGE => 'Notify faculty about changes in class schedules',
            self::CLASS_CANCELLATION => 'Inform faculty about cancelled classes',
            self::STUDENT_ENROLLMENT => 'Alert about new student enrollments in classes',
            self::STUDENT_SECTION_CHANGE => 'Notify about students changing sections',
            self::GRADE_SUBMISSION_REMINDER => 'Remind faculty to submit grades',
            self::MEETING_ANNOUNCEMENT => 'Announce faculty meetings and events',
            self::SYSTEM_MAINTENANCE => 'Inform about system maintenance schedules',
            self::CURRICULUM_UPDATE => 'Notify about curriculum changes and updates',
            self::EXAM_SCHEDULE => 'Share examination schedules and updates',
            self::DOCUMENT_SUBMISSION => 'Remind about document submission deadlines',
            self::ROUTE_STATUS_CHANGE => 'Notify about changes in route accessibility',
            self::ROUTE_DISABLED => 'Inform about disabled routes and features',
            self::ROUTE_MAINTENANCE => 'Alert about routes under maintenance',
            self::ROUTE_ENABLED => 'Notify when routes are re-enabled',

            // Student notifications
            self::ENROLLMENT_APPROVED => 'Notify students about approved enrollment',
            self::ENROLLMENT_REJECTED => 'Inform students about rejected enrollment',
            self::TUITION_DUE => 'Remind students about upcoming tuition payments',
            self::TUITION_OVERDUE => 'Alert students about overdue tuition payments',
            self::TUITION_PAYMENT_CONFIRMED => 'Confirm successful tuition payments',
            self::GRADE_RELEASED => 'Notify students when grades are available',
            self::SCHEDULE_UPDATED => 'Inform students about schedule changes',
            self::CLASS_CANCELLED => 'Alert students about cancelled classes',
            self::EXAM_REMINDER => 'Remind students about upcoming exams',
            self::ASSIGNMENT_DUE => 'Notify students about assignment deadlines',
            self::GENERAL_ANNOUNCEMENT => 'Share general announcements with students',
            self::ACADEMIC_WARNING => 'Alert students about academic performance issues',
            self::SCHOLARSHIP_NOTIFICATION => 'Inform students about scholarship opportunities',
            self::LIBRARY_NOTICE => 'Share library-related notices with students',

            // Common notifications
            self::CUSTOM => 'Send custom notifications with personalized content',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            // Faculty notifications
            self::SCHEDULE_CHANGE => 'heroicon-o-calendar',
            self::CLASS_CANCELLATION => 'heroicon-o-x-circle',
            self::STUDENT_ENROLLMENT => 'heroicon-o-user-plus',
            self::STUDENT_SECTION_CHANGE => 'heroicon-o-arrow-path',
            self::GRADE_SUBMISSION_REMINDER => 'heroicon-o-academic-cap',
            self::MEETING_ANNOUNCEMENT => 'heroicon-o-megaphone',
            self::SYSTEM_MAINTENANCE => 'heroicon-o-wrench-screwdriver',
            self::CURRICULUM_UPDATE => 'heroicon-o-book-open',
            self::EXAM_SCHEDULE => 'heroicon-o-clipboard-document-list',
            self::DOCUMENT_SUBMISSION => 'heroicon-o-document-text',
            self::ROUTE_STATUS_CHANGE => 'heroicon-o-map',
            self::ROUTE_DISABLED => 'heroicon-o-x-circle',
            self::ROUTE_MAINTENANCE => 'heroicon-o-wrench-screwdriver',
            self::ROUTE_ENABLED => 'heroicon-o-check-circle',

            // Student notifications
            self::ENROLLMENT_APPROVED => 'heroicon-o-check-circle',
            self::ENROLLMENT_REJECTED => 'heroicon-o-x-circle',
            self::TUITION_DUE => 'heroicon-o-banknote',
            self::TUITION_OVERDUE => 'heroicon-o-exclamation-triangle',
            self::TUITION_PAYMENT_CONFIRMED => 'heroicon-o-check-badge',
            self::GRADE_RELEASED => 'heroicon-o-academic-cap',
            self::SCHEDULE_UPDATED => 'heroicon-o-calendar',
            self::CLASS_CANCELLED => 'heroicon-o-x-circle',
            self::EXAM_REMINDER => 'heroicon-o-clock',
            self::ASSIGNMENT_DUE => 'heroicon-o-document-text',
            self::GENERAL_ANNOUNCEMENT => 'heroicon-o-megaphone',
            self::ACADEMIC_WARNING => 'heroicon-o-exclamation-triangle',
            self::SCHOLARSHIP_NOTIFICATION => 'heroicon-o-gift',
            self::LIBRARY_NOTICE => 'heroicon-o-book-open',

            // Common notifications
            self::CUSTOM => 'heroicon-o-chat-bubble-left-right',
        };
    }

    public function getDefaultPriority(): NotificationPriority
    {
        return match ($this) {
            // Faculty notifications
            self::SCHEDULE_CHANGE => NotificationPriority::HIGH,
            self::CLASS_CANCELLATION => NotificationPriority::URGENT,
            self::STUDENT_ENROLLMENT => NotificationPriority::NORMAL,
            self::STUDENT_SECTION_CHANGE => NotificationPriority::NORMAL,
            self::GRADE_SUBMISSION_REMINDER => NotificationPriority::HIGH,
            self::MEETING_ANNOUNCEMENT => NotificationPriority::NORMAL,
            self::SYSTEM_MAINTENANCE => NotificationPriority::URGENT,
            self::CURRICULUM_UPDATE => NotificationPriority::HIGH,
            self::EXAM_SCHEDULE => NotificationPriority::HIGH,
            self::DOCUMENT_SUBMISSION => NotificationPriority::HIGH,
            self::ROUTE_STATUS_CHANGE => NotificationPriority::NORMAL,
            self::ROUTE_DISABLED => NotificationPriority::HIGH,
            self::ROUTE_MAINTENANCE => NotificationPriority::HIGH,
            self::ROUTE_ENABLED => NotificationPriority::NORMAL,

            // Student notifications
            self::ENROLLMENT_APPROVED => NotificationPriority::HIGH,
            self::ENROLLMENT_REJECTED => NotificationPriority::HIGH,
            self::TUITION_DUE => NotificationPriority::HIGH,
            self::TUITION_OVERDUE => NotificationPriority::URGENT,
            self::TUITION_PAYMENT_CONFIRMED => NotificationPriority::NORMAL,
            self::GRADE_RELEASED => NotificationPriority::HIGH,
            self::SCHEDULE_UPDATED => NotificationPriority::HIGH,
            self::CLASS_CANCELLED => NotificationPriority::URGENT,
            self::EXAM_REMINDER => NotificationPriority::HIGH,
            self::ASSIGNMENT_DUE => NotificationPriority::HIGH,
            self::GENERAL_ANNOUNCEMENT => NotificationPriority::NORMAL,
            self::ACADEMIC_WARNING => NotificationPriority::URGENT,
            self::SCHOLARSHIP_NOTIFICATION => NotificationPriority::NORMAL,
            self::LIBRARY_NOTICE => NotificationPriority::LOW,

            // Common notifications
            self::CUSTOM => NotificationPriority::NORMAL,
        };
    }

    public function getTemplate(): array
    {
        return match ($this) {
            self::SCHEDULE_CHANGE => [
                'title' => 'Schedule Change Alert',
                'message' => 'Your class schedule has been updated. Please review the changes.',
                'action_text' => 'View Schedule',
                'action_url' => '/faculty/schedule',
            ],
            self::CLASS_CANCELLATION => [
                'title' => 'Class Cancelled',
                'message' => 'Your class has been cancelled. Students have been notified.',
                'action_text' => 'View Details',
                'action_url' => '/faculty/classes',
            ],
            self::STUDENT_ENROLLMENT => [
                'title' => 'New Student Enrollment',
                'message' => 'A new student has enrolled in your class.',
                'action_text' => 'View Students',
                'action_url' => '/faculty/students',
            ],
            self::STUDENT_SECTION_CHANGE => [
                'title' => 'Student Section Change',
                'message' => 'A student has changed sections in your subject.',
                'action_text' => 'View Changes',
                'action_url' => '/faculty/students',
            ],
            self::GRADE_SUBMISSION_REMINDER => [
                'title' => 'Grade Submission Reminder',
                'message' => 'Please submit grades for your classes before the deadline.',
                'action_text' => 'Submit Grades',
                'action_url' => '/faculty/grades',
            ],
            self::MEETING_ANNOUNCEMENT => [
                'title' => 'Faculty Meeting',
                'message' => 'You have been invited to a faculty meeting.',
                'action_text' => 'View Details',
                'action_url' => '/faculty/meetings',
            ],
            self::SYSTEM_MAINTENANCE => [
                'title' => 'System Maintenance',
                'message' => 'The system will undergo maintenance. Please save your work.',
                'action_text' => 'Learn More',
                'action_url' => '/maintenance',
            ],
            self::CURRICULUM_UPDATE => [
                'title' => 'Curriculum Update',
                'message' => 'The curriculum for your subject has been updated.',
                'action_text' => 'View Curriculum',
                'action_url' => '/faculty/curriculum',
            ],
            self::EXAM_SCHEDULE => [
                'title' => 'Exam Schedule',
                'message' => 'The examination schedule has been released.',
                'action_text' => 'View Schedule',
                'action_url' => '/faculty/exams',
            ],
            self::DOCUMENT_SUBMISSION => [
                'title' => 'Document Submission',
                'message' => 'Please submit required documents before the deadline.',
                'action_text' => 'Submit Documents',
                'action_url' => '/faculty/documents',
            ],
            self::ROUTE_STATUS_CHANGE => [
                'title' => 'Route Status Changed',
                'message' => 'A route you use has had its status changed.',
                'action_text' => 'View Details',
                'action_url' => '/dashboard',
            ],
            self::ROUTE_DISABLED => [
                'title' => 'Route Disabled',
                'message' => 'A feature you use has been temporarily disabled.',
                'action_text' => 'Learn More',
                'action_url' => '/dashboard',
            ],
            self::ROUTE_MAINTENANCE => [
                'title' => 'Route Under Maintenance',
                'message' => 'A feature is currently under maintenance and will be back soon.',
                'action_text' => 'Check Status',
                'action_url' => '/dashboard',
            ],
            self::ROUTE_ENABLED => [
                'title' => 'Route Re-enabled',
                'message' => 'A previously disabled feature is now available again.',
                'action_text' => 'Access Feature',
                'action_url' => '/dashboard',
            ],

            // Student notification templates
            self::ENROLLMENT_APPROVED => [
                'title' => 'Enrollment Approved',
                'message' => 'Your enrollment has been approved. Welcome to the program!',
                'action_text' => 'View Dashboard',
                'action_url' => '/dashboard',
            ],
            self::ENROLLMENT_REJECTED => [
                'title' => 'Enrollment Status Update',
                'message' => 'Your enrollment application requires additional review.',
                'action_text' => 'Contact Support',
                'action_url' => '/support',
            ],
            self::TUITION_DUE => [
                'title' => 'Tuition Payment Due',
                'message' => 'Your tuition payment is due soon. Please make payment to avoid late fees.',
                'action_text' => 'Pay Now',
                'action_url' => '/tuition',
            ],
            self::TUITION_OVERDUE => [
                'title' => 'Tuition Payment Overdue',
                'message' => 'Your tuition payment is overdue. Please pay immediately to avoid penalties.',
                'action_text' => 'Pay Now',
                'action_url' => '/tuition',
            ],
            self::TUITION_PAYMENT_CONFIRMED => [
                'title' => 'Payment Confirmed',
                'message' => 'Your tuition payment has been successfully processed.',
                'action_text' => 'View Receipt',
                'action_url' => '/tuition',
            ],
            self::GRADE_RELEASED => [
                'title' => 'Grades Available',
                'message' => 'Your grades for this semester have been released.',
                'action_text' => 'View Grades',
                'action_url' => '/grades',
            ],
            self::SCHEDULE_UPDATED => [
                'title' => 'Schedule Updated',
                'message' => 'Your class schedule has been updated. Please review the changes.',
                'action_text' => 'View Schedule',
                'action_url' => '/schedule',
            ],
            self::CLASS_CANCELLED => [
                'title' => 'Class Cancelled',
                'message' => 'One of your classes has been cancelled. Check your schedule for updates.',
                'action_text' => 'View Schedule',
                'action_url' => '/schedule',
            ],
            self::EXAM_REMINDER => [
                'title' => 'Upcoming Exam',
                'message' => 'You have an exam coming up. Make sure you are prepared.',
                'action_text' => 'View Schedule',
                'action_url' => '/schedule',
            ],
            self::ASSIGNMENT_DUE => [
                'title' => 'Assignment Due',
                'message' => 'You have an assignment due soon. Please submit it on time.',
                'action_text' => 'View Assignments',
                'action_url' => '/assignments',
            ],
            self::GENERAL_ANNOUNCEMENT => [
                'title' => 'Important Announcement',
                'message' => 'There is an important announcement for all students.',
                'action_text' => 'Read More',
                'action_url' => '/announcements',
            ],
            self::ACADEMIC_WARNING => [
                'title' => 'Academic Performance Notice',
                'message' => 'Your academic performance requires attention. Please contact your advisor.',
                'action_text' => 'Contact Advisor',
                'action_url' => '/support',
            ],
            self::SCHOLARSHIP_NOTIFICATION => [
                'title' => 'Scholarship Opportunity',
                'message' => 'You may be eligible for a scholarship. Check the details.',
                'action_text' => 'Learn More',
                'action_url' => '/scholarships',
            ],
            self::LIBRARY_NOTICE => [
                'title' => 'Library Notice',
                'message' => 'Important information from the library.',
                'action_text' => 'Visit Library',
                'action_url' => '/library',
            ],

            self::CUSTOM => [
                'title' => '',
                'message' => '',
                'action_text' => '',
                'action_url' => '',
            ],
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
