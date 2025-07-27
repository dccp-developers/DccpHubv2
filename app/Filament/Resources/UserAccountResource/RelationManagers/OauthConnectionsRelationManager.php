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

class OauthConnectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'oauthConnections';

    protected static ?string $title = 'OAuth Connections';

    protected static ?string $modelLabel = 'OAuth Connection';

    protected static ?string $pluralModelLabel = 'OAuth Connections';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('provider')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),

                Forms\Components\TextInput::make('provider_id')
                    ->label('Provider ID')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),

                Forms\Components\KeyValue::make('data')
                    ->label('Provider Data')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Token Expires At')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Connected At')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('provider')
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->color(function (string $state): string {
                        return match (strtolower($state)) {
                            'google' => 'danger',
                            'facebook' => 'primary',
                            'github' => 'gray',
                            'twitter' => 'info',
                            default => 'gray',
                        };
                    })
                    ->icon(function (string $state): string {
                        return match (strtolower($state)) {
                            'google' => 'heroicon-o-globe-alt',
                            'facebook' => 'heroicon-o-globe-alt',
                            'github' => 'heroicon-o-code-bracket',
                            'twitter' => 'heroicon-o-chat-bubble-left-ellipsis',
                            default => 'heroicon-o-link',
                        };
                    }),

                Tables\Columns\TextColumn::make('provider_id')
                    ->label('Provider ID')
                    ->limit(20)
                    ->tooltip(function ($record) {
                        return $record->provider_id;
                    }),

                Tables\Columns\IconColumn::make('token')
                    ->label('Has Token')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->token))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Token Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(function ($record) {
                        if (!$record->expires_at) return 'gray';
                        return $record->expires_at->isPast() ? 'danger' : 'success';
                    })
                    ->formatStateUsing(function ($record) {
                        if (!$record->expires_at) return 'Never';
                        return $record->expires_at->format('M j, Y g:i A');
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Connected')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'google' => 'Google',
                        'facebook' => 'Facebook',
                        'github' => 'GitHub',
                        'twitter' => 'Twitter',
                    ]),

                Tables\Filters\TernaryFilter::make('has_token')
                    ->label('Has Valid Token')
                    ->placeholder('All connections')
                    ->trueLabel('With token')
                    ->falseLabel('Without token')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('token'),
                        false: fn (Builder $query) => $query->whereNull('token'),
                    ),

                Tables\Filters\TernaryFilter::make('token_expired')
                    ->label('Token Status')
                    ->placeholder('All tokens')
                    ->trueLabel('Expired tokens')
                    ->falseLabel('Valid tokens')
                    ->queries(
                        true: fn (Builder $query) => $query->where('expires_at', '<', now()),
                        false: fn (Builder $query) => $query->where('expires_at', '>', now()),
                    ),
            ])
            ->headerActions([
                // OAuth connections are typically created through login flow
                // So we don't provide a create action here
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('refresh_token')
                    ->label('Refresh Token')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => $record->refresh_token && $record->expires_at && $record->expires_at->isPast())
                    ->action(function ($record) {
                        // Implement token refresh logic here
                        // This would depend on your OAuth implementation
                        \Filament\Notifications\Notification::make()
                            ->info()
                            ->title('Token refresh')
                            ->body('Token refresh functionality needs to be implemented.')
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Disconnect')
                    ->modalHeading('Disconnect OAuth Provider')
                    ->modalDescription('Are you sure you want to disconnect this OAuth provider? The user will need to reconnect if they want to use this provider again.')
                    ->successNotificationTitle('OAuth provider disconnected'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Disconnect Selected')
                        ->modalHeading('Disconnect OAuth Providers')
                        ->modalDescription('Are you sure you want to disconnect the selected OAuth providers?')
                        ->successNotificationTitle('OAuth providers disconnected'),
                ]),
            ])
            ->emptyStateHeading('No OAuth Connections')
            ->emptyStateDescription('This user has not connected any OAuth providers.')
            ->emptyStateIcon('heroicon-o-link');
    }
}
