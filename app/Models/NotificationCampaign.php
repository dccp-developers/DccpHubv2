<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class NotificationCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'priority',
        'status',
        'notification_title',
        'notification_message',
        'action_text',
        'action_url',
        'additional_data',
        'recipient_ids',
        'send_to_all_faculty',
        'recipient_filters',
        'scheduled_at',
        'sent_at',
        'expires_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'error_log',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'priority' => NotificationPriority::class,
        'status' => NotificationStatus::class,
        'additional_data' => 'array',
        'recipient_ids' => 'array',
        'recipient_filters' => 'array',
        'error_log' => 'array',
        'send_to_all_faculty' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created the campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the campaign.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the recipients of the campaign.
     */
    public function recipients()
    {
        if ($this->send_to_all_faculty) {
            return User::where('role', 'faculty')->get();
        }

        if ($this->recipient_ids) {
            return User::whereIn('id', $this->recipient_ids)->get();
        }

        return collect();
    }

    /**
     * Check if the campaign is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === NotificationStatus::SCHEDULED &&
               $this->scheduled_at &&
               $this->scheduled_at->isFuture();
    }

    /**
     * Check if the campaign is ready to send.
     */
    public function isReadyToSend(): bool
    {
        return $this->status === NotificationStatus::SCHEDULED &&
               $this->scheduled_at &&
               $this->scheduled_at->isPast();
    }

    /**
     * Get the success rate of the campaign.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return ($this->sent_count / $this->total_recipients) * 100;
    }

    /**
     * Get the failure rate of the campaign.
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return ($this->failed_count / $this->total_recipients) * 100;
    }

    /**
     * Scope to get campaigns ready to send.
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', NotificationStatus::SCHEDULED)
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope to get campaigns by type.
     */
    public function scopeOfType($query, NotificationType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get campaigns by status.
     */
    public function scopeWithStatus($query, NotificationStatus $status)
    {
        return $query->where('status', $status);
    }
}
