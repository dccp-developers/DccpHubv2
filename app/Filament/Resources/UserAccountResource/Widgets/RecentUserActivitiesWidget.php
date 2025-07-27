<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\Widgets;

use App\Models\User;
use App\Enums\UserRole;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;


class RecentUserActivitiesWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent User Activities';

    protected static ?string $description = 'Latest user registrations and updates';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('profile_photo_path')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->email),

                Tables\Columns\TextColumn::make('role')
                    ->formatStateUsing(fn (string $state): string => UserRole::from($state)->getLabel())
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'admin' => 'danger',
                            'faculty' => 'success',
                            'student' => 'primary',
                            'guest' => 'warning',
                            'staff' => 'info',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->email_verified_at))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('activity')
                    ->label('Activity')
                    ->getStateUsing(function ($record) {
                        $createdRecently = $record->created_at->isAfter(now()->subHours(24));
                        return $createdRecently ? 'New Registration' : 'Profile Updated';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $createdRecently = $record->created_at->isAfter(now()->subHours(24));
                        return $createdRecently ? 'success' : 'info';
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.user-accounts.view', $record))
                    ->openUrlInNewTab(false),
            ])
            ->paginated(false)
            ->emptyStateHeading('No recent activities')
            ->emptyStateDescription('No user activities found in the recent period.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10];
    }
}
