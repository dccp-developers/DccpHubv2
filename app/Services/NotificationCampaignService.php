<?php

namespace App\Services;

use App\Models\NotificationCampaign;
use App\Models\StudentNotificationCampaign;
use App\Models\User;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Services\NotificationService;
use App\Mail\NotificationCampaignEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationCampaignService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Send a notification campaign.
     */
    public function sendCampaign(NotificationCampaign $campaign): bool
    {
        if ($campaign->status !== NotificationStatus::SCHEDULED) {
            return false;
        }

        DB::beginTransaction();
        
        try {
            // Get recipients
            $recipients = $this->getRecipients($campaign);
            
            if ($recipients->isEmpty()) {
                throw new \Exception('No recipients found for campaign');
            }

            // Update campaign status
            $campaign->update([
                'status' => NotificationStatus::SENT,
                'sent_at' => now(),
                'total_recipients' => $recipients->count(),
            ]);

            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            // Send notifications to each recipient
            $emailSentCount = 0;
            $emailFailedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    // Send in-app notification
                    $this->notificationService->sendToUser(
                        $recipient,
                        $campaign->notification_title,
                        $campaign->notification_message,
                        $campaign->type->value, // type parameter
                        $campaign->additional_data ?? [], // data parameter
                        $campaign->action_url, // actionUrl parameter
                        $campaign->action_text, // actionText parameter
                        $campaign->priority->value, // priority parameter
                        $campaign->expires_at // expiresAt parameter
                    );

                    $sentCount++;

                    // Send email notification if enabled
                    if ($campaign->send_email && $recipient->email) {
                        try {
                            Mail::to($recipient->email)->send(new NotificationCampaignEmail(
                                $recipient,
                                $campaign->email_subject ?: $campaign->notification_title,
                                $campaign->email_message ?: $campaign->notification_message,
                                $campaign->notification_title,
                                $campaign->action_url,
                                $campaign->action_text,
                                $campaign->priority->value
                            ));

                            $emailSentCount++;
                        } catch (\Exception $emailError) {
                            $emailFailedCount++;
                            $errors[] = [
                                'user_id' => $recipient->id,
                                'type' => 'email',
                                'error' => $emailError->getMessage(),
                                'timestamp' => now()->toISOString(),
                            ];

                            Log::error('Failed to send email notification', [
                                'campaign_id' => $campaign->id,
                                'user_id' => $recipient->id,
                                'error' => $emailError->getMessage(),
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'user_id' => $recipient->id,
                        'type' => 'notification',
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString(),
                    ];

                    Log::error('Failed to send notification to user', [
                        'campaign_id' => $campaign->id,
                        'user_id' => $recipient->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update campaign statistics
            $campaign->update([
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'email_sent_count' => $emailSentCount,
                'email_failed_count' => $emailFailedCount,
                'error_log' => $errors,
                'status' => $failedCount > 0 && $sentCount === 0 ? NotificationStatus::FAILED : NotificationStatus::SENT,
            ]);

            DB::commit();
            
            Log::info('Notification campaign sent', [
                'campaign_id' => $campaign->id,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            $campaign->update([
                'status' => NotificationStatus::FAILED,
                'error_log' => [
                    [
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString(),
                    ]
                ],
            ]);

            Log::error('Failed to send notification campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get recipients for a campaign.
     */
    private function getRecipients(NotificationCampaign $campaign): Collection
    {
        if ($campaign->send_to_all_faculty) {
            return User::where('role', 'faculty')->get();
        }

        if ($campaign->recipient_ids) {
            return User::whereIn('id', $campaign->recipient_ids)->get();
        }

        // Apply filters if any
        if ($campaign->recipient_filters) {
            $query = User::where('role', 'faculty');
            
            foreach ($campaign->recipient_filters as $filter => $value) {
                switch ($filter) {
                    case 'department':
                        $query->where('department', $value);
                        break;
                    case 'status':
                        $query->where('status', $value);
                        break;
                    // Add more filters as needed
                }
            }
            
            return $query->get();
        }

        return collect();
    }

    /**
     * Schedule a campaign for later sending.
     */
    public function scheduleCampaign(NotificationCampaign $campaign, \DateTime $scheduledAt): bool
    {
        try {
            $campaign->update([
                'status' => NotificationStatus::SCHEDULED,
                'scheduled_at' => $scheduledAt,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to schedule campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process scheduled campaigns.
     */
    public function processScheduledCampaigns(): int
    {
        $campaigns = NotificationCampaign::readyToSend()->get();
        $processed = 0;

        foreach ($campaigns as $campaign) {
            if ($this->sendCampaign($campaign)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Create a campaign from a notification type template.
     */
    public function createFromTemplate(
        NotificationType $type,
        array $customData = [],
        ?array $recipients = null
    ): NotificationCampaign {
        $template = $type->getTemplate();
        
        return NotificationCampaign::create([
            'title' => $customData['title'] ?? $template['title'],
            'description' => $customData['description'] ?? "Campaign for {$type->getLabel()}",
            'type' => $type,
            'priority' => $customData['priority'] ?? $type->getDefaultPriority(),
            'status' => NotificationStatus::DRAFT,
            'notification_title' => $customData['notification_title'] ?? $template['title'],
            'notification_message' => $customData['notification_message'] ?? $template['message'],
            'action_text' => $customData['action_text'] ?? $template['action_text'],
            'action_url' => $customData['action_url'] ?? $template['action_url'],
            'additional_data' => $customData['additional_data'] ?? [],
            'recipient_ids' => $recipients,
            'send_to_all_faculty' => $recipients === null,
            'send_email' => $customData['send_email'] ?? false,
            'email_subject' => $customData['email_subject'] ?? null,
            'email_message' => $customData['email_message'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Duplicate an existing campaign.
     */
    public function duplicateCampaign(NotificationCampaign $campaign): NotificationCampaign
    {
        $newCampaign = $campaign->replicate();
        $newCampaign->title = $campaign->title . ' (Copy)';
        $newCampaign->status = NotificationStatus::DRAFT;
        $newCampaign->scheduled_at = null;
        $newCampaign->sent_at = null;
        $newCampaign->total_recipients = 0;
        $newCampaign->sent_count = 0;
        $newCampaign->failed_count = 0;
        $newCampaign->error_log = null;
        $newCampaign->created_by = Auth::id();
        $newCampaign->updated_by = null;
        $newCampaign->save();

        return $newCampaign;
    }

    /**
     * Create a student campaign from template.
     */
    public function createStudentCampaignFromTemplate(
        NotificationType $type,
        array $customData = [],
        ?array $recipients = null
    ): StudentNotificationCampaign {
        $template = $type->getTemplate();

        return StudentNotificationCampaign::create([
            'title' => $customData['title'] ?? $template['title'],
            'description' => $customData['description'] ?? "Student campaign for {$type->getLabel()}",
            'type' => $type,
            'priority' => $customData['priority'] ?? $type->getDefaultPriority(),
            'status' => NotificationStatus::DRAFT,
            'notification_title' => $customData['notification_title'] ?? $template['title'],
            'notification_message' => $customData['notification_message'] ?? $template['message'],
            'action_text' => $customData['action_text'] ?? $template['action_text'],
            'action_url' => $customData['action_url'] ?? $template['action_url'],
            'additional_data' => $customData['additional_data'] ?? [],
            'recipient_ids' => $recipients,
            'send_to_all_students' => $recipients === null,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Send a student notification campaign.
     */
    public function sendStudentCampaign(StudentNotificationCampaign $campaign): bool
    {
        if ($campaign->status !== NotificationStatus::SCHEDULED) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Get recipients
            $recipients = $this->getStudentRecipients($campaign);

            if ($recipients->isEmpty()) {
                throw new \Exception('No student recipients found for campaign');
            }

            // Update campaign status
            $campaign->update([
                'status' => NotificationStatus::SENT,
                'sent_at' => now(),
                'total_recipients' => $recipients->count(),
            ]);

            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            // Send notifications to each recipient
            foreach ($recipients as $recipient) {
                try {
                    $this->notificationService->sendToUser(
                        $recipient,
                        $campaign->notification_title,
                        $campaign->notification_message,
                        $campaign->type->value,
                        $campaign->additional_data ?? [],
                        $campaign->action_url,
                        $campaign->action_text,
                        $campaign->priority->value,
                        $campaign->expires_at
                    );

                    $sentCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'user_id' => $recipient->id,
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString(),
                    ];

                    Log::error('Failed to send notification to student', [
                        'campaign_id' => $campaign->id,
                        'user_id' => $recipient->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update campaign statistics
            $campaign->update([
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'error_log' => $errors,
                'status' => $failedCount > 0 && $sentCount === 0 ? NotificationStatus::FAILED : NotificationStatus::SENT,
            ]);

            DB::commit();

            Log::info('Student notification campaign sent', [
                'campaign_id' => $campaign->id,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            $campaign->update([
                'status' => NotificationStatus::FAILED,
                'error_log' => [
                    [
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString(),
                    ]
                ],
            ]);

            Log::error('Failed to send student notification campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get student recipients for a campaign.
     */
    protected function getStudentRecipients(StudentNotificationCampaign $campaign): Collection
    {
        if ($campaign->send_to_all_students) {
            return User::where('role', 'student')->get();
        }

        if ($campaign->recipient_ids) {
            return User::whereIn('id', $campaign->recipient_ids)
                      ->where('role', 'student')
                      ->get();
        }

        return collect();
    }

    /**
     * Get campaign statistics.
     */
    public function getCampaignStats(): array
    {
        return [
            'total_campaigns' => NotificationCampaign::count(),
            'sent_campaigns' => NotificationCampaign::withStatus(NotificationStatus::SENT)->count(),
            'scheduled_campaigns' => NotificationCampaign::withStatus(NotificationStatus::SCHEDULED)->count(),
            'failed_campaigns' => NotificationCampaign::withStatus(NotificationStatus::FAILED)->count(),
            'draft_campaigns' => NotificationCampaign::withStatus(NotificationStatus::DRAFT)->count(),
        ];
    }

    /**
     * Get student campaign statistics.
     */
    public function getStudentCampaignStats(): array
    {
        return [
            'total_campaigns' => StudentNotificationCampaign::count(),
            'sent_campaigns' => StudentNotificationCampaign::where('status', NotificationStatus::SENT)->count(),
            'scheduled_campaigns' => StudentNotificationCampaign::where('status', NotificationStatus::SCHEDULED)->count(),
            'failed_campaigns' => StudentNotificationCampaign::where('status', NotificationStatus::FAILED)->count(),
            'draft_campaigns' => StudentNotificationCampaign::where('status', NotificationStatus::DRAFT)->count(),
        ];
    }
}
