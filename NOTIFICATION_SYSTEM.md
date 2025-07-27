# Faculty Notification Management System

## Overview

A comprehensive notification management system built with Filament for administrators to send targeted notifications to faculty members. The system supports 11 different notification types with templates, scheduling, and detailed tracking.

## Features

### 🎯 **Notification Types**
1. **Schedule Change** - Notify about class schedule updates
2. **Class Cancellation** - Inform about cancelled classes  
3. **Student Enrollment** - Alert about new student enrollments
4. **Student Section Change** - Notify about section transfers
5. **Grade Submission Reminder** - Remind about grade deadlines
6. **Meeting Announcement** - Announce faculty meetings
7. **System Maintenance** - Inform about system downtime
8. **Curriculum Update** - Share curriculum changes
9. **Exam Schedule** - Distribute exam schedules
10. **Document Submission** - Remind about document deadlines
11. **Custom** - Send personalized notifications

### 📊 **Priority Levels**
- **Low** (Gray) - General information
- **Normal** (Blue) - Standard notifications  
- **High** (Orange) - Important updates
- **Urgent** (Red) - Critical alerts

### 🎛️ **Campaign Management**
- **Draft** - Work in progress
- **Scheduled** - Set for future delivery
- **Sent** - Successfully delivered
- **Failed** - Delivery issues

## How to Use

### 🚀 **Quick Start**

1. **Access Admin Panel**: Navigate to `/admin` and login
2. **Go to Notifications**: Find "Notification Campaigns" in the Communication section
3. **Create Campaign**: Click "Create Campaign" or "Quick Create"

### 📝 **Creating Campaigns**

#### **Method 1: Quick Create**
1. Click "Quick Create" button
2. Select notification type (auto-fills template)
3. Customize title and message
4. Choose recipients (all faculty or specific)
5. Send immediately or save as draft

#### **Method 2: Full Campaign**
1. Click "Create Campaign"
2. Fill campaign information:
   - Title and description
   - Select notification type
   - Set priority level
3. Customize notification content:
   - Title and message
   - Action button text and URL
4. Choose recipients:
   - Toggle "Send to All Faculty" OR
   - Select specific faculty members
5. Set scheduling (optional):
   - Schedule for later delivery
   - Set expiration date

### 📅 **Scheduling & Automation**

#### **Schedule Campaigns**
- Set future delivery dates
- Automatic processing via cron job
- Manual processing available

#### **Command Line**
```bash
# Process scheduled campaigns
php artisan notifications:process-scheduled
```

#### **Cron Job Setup**
Add to your crontab:
```bash
* * * * * cd /path/to/project && php artisan notifications:process-scheduled
```

### 🎯 **Targeting Recipients**

#### **Send to All Faculty**
- Toggle "Send to All Faculty"
- Automatically includes all users with role 'faculty'

#### **Select Specific Faculty**
- Disable "Send to All Faculty"
- Choose individual faculty members from dropdown
- Searchable interface for large faculty lists

### 📈 **Tracking & Analytics**

#### **Campaign Statistics**
- Total campaigns created
- Success/failure rates
- Delivery statistics
- Real-time dashboard

#### **Individual Campaign Tracking**
- Total recipients
- Successfully sent count
- Failed delivery count
- Error logs for troubleshooting

### 🔧 **Advanced Features**

#### **Template System**
- Pre-built templates for each notification type
- Default priorities and content
- Customizable action buttons
- Smart URL routing

#### **Bulk Operations**
- Send multiple campaigns at once
- Bulk delete campaigns
- Mass scheduling

#### **Testing**
- Send test notifications to yourself
- Preview before sending
- Duplicate existing campaigns

## API Integration

### **Programmatic Campaign Creation**

```php
use App\Services\NotificationCampaignService;
use App\Enums\NotificationType;

$service = app(NotificationCampaignService::class);

// Create from template
$campaign = $service->createFromTemplate(
    NotificationType::SCHEDULE_CHANGE,
    [
        'notification_title' => 'Custom Title',
        'notification_message' => 'Custom message',
    ]
);

// Send immediately
$service->sendCampaign($campaign);
```

### **Direct Notification Sending**

```php
use App\Facades\Notify;

// Send to single faculty
Notify::info($user, 'Title', 'Message');

// Send to all faculty
Notify::toFaculty('Title', 'Message');

// With action button
Notify::warning($user, 'Title', 'Message', [], '/url', 'Button Text');
```

## Best Practices

### 📋 **Content Guidelines**
- Keep titles under 50 characters
- Messages should be clear and actionable
- Include relevant action buttons
- Set appropriate priority levels

### ⏰ **Timing**
- Schedule non-urgent notifications for business hours
- Use urgent priority sparingly
- Set expiration dates for time-sensitive content

### 🎯 **Targeting**
- Use specific targeting when possible
- Avoid over-notification
- Consider faculty workload and schedules

### 📊 **Monitoring**
- Review delivery statistics regularly
- Check error logs for failed deliveries
- Monitor engagement through action clicks

## Troubleshooting

### **Common Issues**

#### **Failed Deliveries**
- Check user email addresses
- Verify notification service configuration
- Review error logs in campaign details

#### **Scheduling Issues**
- Ensure cron job is running
- Check server timezone settings
- Verify scheduled_at timestamps

#### **Permission Issues**
- Confirm admin role access
- Check Filament panel permissions
- Verify user authentication

### **Support**
- Check application logs: `storage/logs/laravel.log`
- Review notification service logs
- Contact system administrator for technical issues

## Configuration

### **Environment Variables**
```env
# Pusher Configuration (for real-time notifications)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# Web Push Notifications
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
```

### **Customization**
- Modify notification types in `App\Enums\NotificationType`
- Adjust templates and priorities
- Customize Filament resource appearance
- Add new recipient filtering options

---

**Version**: 1.0  
**Last Updated**: July 27, 2025  
**Developed for**: DCCP Faculty Management System
