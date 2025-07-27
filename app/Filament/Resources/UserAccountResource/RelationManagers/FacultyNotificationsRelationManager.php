<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FacultyNotificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'facultyNotifications';

    protected static ?string $title = 'Faculty Notifications';

    protected static ?string $modelLabel = 'Notification';

    protected static ?string $pluralModelLabel = 'Faculty Notifications';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('message')
                    ->required()
                    ->rows(4),

                Forms\Components\Select::make('type')
                    ->options([
                        'info' => 'Information',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ])
                    ->default('info')
                    ->required(),

                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->default('normal')
                    ->required(),

                Forms\Components\TextInput::make('action_url')
                    ->label('Action URL')
                    ->url()
                    ->maxLength(255),

                Forms\Components\TextInput::make('action_text')
                    ->label('Action Button Text')
                    ->maxLength(255),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->helperText('Leave empty for notifications that never expire'),

                Forms\Components\KeyValue::make('data')
                    ->label('Additional Data')
                    ->helperText('Additional data for the notification'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('primary'),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(50),

                Tables\Columns\TextColumn::make('message')
                    ->limit(100)
                    ->tooltip(function ($record) {
                        return $record->message;
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'info' => 'info',
                            'success' => 'success',
                            'warning' => 'warning',
                            'error' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->icon(function (string $state): string {
                        return match ($state) {
                            'info' => 'heroicon-o-information-circle',
                            'success' => 'heroicon-o-check-circle',
                            'warning' => 'heroicon-o-exclamation-triangle',
                            'error' => 'heroicon-o-x-circle',
                            default => 'heroicon-o-bell',
                        };
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'low' => 'gray',
                            'normal' => 'info',
                            'high' => 'warning',
                            'urgent' => 'danger',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('action_text')
                    ->label('Action')
                    ->placeholder('No action')
                    ->badge()
                    ->color('primary')
                    ->visible(fn ($record) => !empty($record->action_text)),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->color(function ($record) {
                        if (!$record->expires_at) return 'gray';
                        return $record->expires_at->isPast() ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                Tables\Columns\TextColumn::make('read_at')
                    ->label('Read At')
                    ->dateTime()
                    ->placeholder('Unread')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Read Status')
                    ->placeholder('All notifications')
                    ->trueLabel('Read notifications')
                    ->falseLabel('Unread notifications'),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'info' => 'Information',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_expired')
                    ->label('Expiration Status')
                    ->placeholder('All notifications')
                    ->trueLabel('Expired notifications')
                    ->falseLabel('Active notifications')
                    ->queries(
                        true: fn (Builder $query) => $query->where('expires_at', '<', now()),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        }),
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Send Notification')
                    ->icon('heroicon-o-bell')
                    ->modalHeading('Send Faculty Notification')
                    ->successNotificationTitle('Notification sent'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('mark_as_read')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-envelope-open')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_read)
                    ->action(function ($record) {
                        $record->update([
                            'is_read' => true,
                            'read_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Marked as read')
                            ->send();
                    }),

                Tables\Actions\Action::make('mark_as_unread')
                    ->label('Mark as Unread')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn ($record) => $record->is_read)
                    ->action(function ($record) {
                        $record->update([
                            'is_read' => false,
                            'read_at' => null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Marked as unread')
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('Notification deleted'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_read')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-envelope-open')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_read' => true,
                                    'read_at' => now(),
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Notifications marked as read')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotificationTitle('Notifications deleted'),
                ]),
            ])
            ->emptyStateHeading('No Notifications')
            ->emptyStateDescription('This user has not received any faculty notifications.')
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->defaultSort('created_at', 'desc');
    }
}
