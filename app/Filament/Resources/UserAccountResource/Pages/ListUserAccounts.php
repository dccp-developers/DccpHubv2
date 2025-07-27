<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\Pages;

use App\Filament\Resources\UserAccountResource;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserAccounts extends ListRecords
{
    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create User Account')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users')
                ->badge($this->getModel()::count()),

            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge($this->getModel()::where('is_active', true)->count())
                ->badgeColor('success'),

            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge($this->getModel()::where('is_active', false)->count())
                ->badgeColor('danger'),

            'unverified' => Tab::make('Unverified')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('email_verified_at'))
                ->badge($this->getModel()::whereNull('email_verified_at')->count())
                ->badgeColor('warning'),

            'admins' => Tab::make('Administrators')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'admin'))
                ->badge($this->getModel()::where('role', 'admin')->count())
                ->badgeColor('danger'),

            'faculty' => Tab::make('Faculty')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'faculty'))
                ->badge($this->getModel()::where('role', 'faculty')->count())
                ->badgeColor('success'),

            'students' => Tab::make('Students')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'student'))
                ->badge($this->getModel()::where('role', 'student')->count())
                ->badgeColor('primary'),

            'recent' => Tab::make('Recent')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                ->badge($this->getModel()::where('created_at', '>=', now()->subDays(7))->count())
                ->badgeColor('info'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserAccountResource\Widgets\UserAccountStatsWidget::class,
            UserAccountResource\Widgets\UserRoleDistributionWidget::class,
            UserAccountResource\Widgets\RecentUserActivitiesWidget::class,
        ];
    }
}
