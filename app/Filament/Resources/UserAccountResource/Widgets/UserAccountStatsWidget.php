<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\Widgets;

use App\Models\User;
use App\Services\UserAccountService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class UserAccountStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('user_account_stats', 300, function () {
            return app(UserAccountService::class)->getUserStatistics();
        });

        return [
            Stat::make('Total Users', $stats['total_users'])
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart($this->getUserGrowthChart()),

            Stat::make('Active Users', $stats['active_users'])
                ->description('Currently active accounts')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.user-accounts.index', ['tableFilters[is_active][value]' => true])),

            Stat::make('Inactive Users', $stats['inactive_users'])
                ->description('Deactivated accounts')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.user-accounts.index', ['tableFilters[is_active][value]' => false])),

            Stat::make('Unverified Emails', $stats['unverified_users'])
                ->description('Pending email verification')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.user-accounts.index', ['tableFilters[email_verified_at][value]' => false])),

            Stat::make('Recent Registrations', $stats['recent_registrations'])
                ->description('New users (last 30 days)')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.user-accounts.index', ['activeTab' => 'recent'])),

            Stat::make('2FA Enabled', $stats['users_with_2fa'])
                ->description('Users with two-factor auth')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),
        ];
    }

    protected function getUserGrowthChart(): array
    {
        return Cache::remember('user_growth_chart', 3600, function () {
            $data = [];
            
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = User::whereDate('created_at', $date)->count();
                $data[] = $count;
            }
            
            return $data;
        });
    }

    public function getColumns(): int
    {
        return 3;
    }
}
