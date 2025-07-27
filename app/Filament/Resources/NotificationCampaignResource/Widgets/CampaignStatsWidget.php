<?php

namespace App\Filament\Resources\NotificationCampaignResource\Widgets;

use App\Models\NotificationCampaign;
use App\Enums\NotificationStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCampaigns = NotificationCampaign::count();
        $sentCampaigns = NotificationCampaign::where('status', NotificationStatus::SENT)->count();
        $scheduledCampaigns = NotificationCampaign::where('status', NotificationStatus::SCHEDULED)->count();
        $draftCampaigns = NotificationCampaign::where('status', NotificationStatus::DRAFT)->count();
        $failedCampaigns = NotificationCampaign::where('status', NotificationStatus::FAILED)->count();

        $totalRecipients = NotificationCampaign::sum('total_recipients');
        $totalSent = NotificationCampaign::sum('sent_count');
        $totalFailed = NotificationCampaign::sum('failed_count');

        $successRate = $totalRecipients > 0 ? round(($totalSent / $totalRecipients) * 100, 1) : 0;

        return [
            Stat::make('Total Campaigns', $totalCampaigns)
                ->description('All notification campaigns')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('primary'),

            Stat::make('Sent Campaigns', $sentCampaigns)
                ->description('Successfully sent')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Scheduled Campaigns', $scheduledCampaigns)
                ->description('Waiting to be sent')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Draft Campaigns', $draftCampaigns)
                ->description('Not yet scheduled')
                ->descriptionIcon('heroicon-m-document')
                ->color('gray'),

            Stat::make('Success Rate', $successRate . '%')
                ->description("$totalSent sent out of $totalRecipients")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger')),

            Stat::make('Failed Notifications', $totalFailed)
                ->description('Delivery failures')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
