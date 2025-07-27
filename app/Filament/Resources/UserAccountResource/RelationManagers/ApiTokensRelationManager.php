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

class ApiTokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $title = 'API Tokens';

    protected static ?string $modelLabel = 'API Token';

    protected static ?string $pluralModelLabel = 'API Tokens';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('A descriptive name for this token'),

                Forms\Components\TagsInput::make('abilities')
                    ->label('Permissions')
                    ->suggestions([
                        'read',
                        'create',
                        'update',
                        'delete',
                        'manage-users',
                        'manage-settings',
                    ])
                    ->helperText('Select the permissions this token should have'),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->helperText('Leave empty for tokens that never expire'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Token Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('abilities')
                    ->label('Permissions')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->tooltip(function ($record) {
                        return implode(', ', $record->abilities ?? []);
                    }),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->placeholder('Never used')
                    ->color(function ($record) {
                        if (!$record->last_used_at) return 'gray';
                        $daysSinceUsed = $record->last_used_at->diffInDays(now());
                        return match (true) {
                            $daysSinceUsed <= 7 => 'success',
                            $daysSinceUsed <= 30 => 'warning',
                            default => 'danger',
                        };
                    }),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->color(function ($record) {
                        if (!$record->expires_at) return 'gray';
                        return $record->expires_at->isPast() ? 'danger' : 'success';
                    })
                    ->formatStateUsing(function ($record) {
                        if (!$record->expires_at) return 'Never';
                        if ($record->expires_at->isPast()) return 'Expired';
                        return $record->expires_at->format('M j, Y');
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        return !$record->expires_at || $record->expires_at->isFuture();
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_expired')
                    ->label('Token Status')
                    ->placeholder('All tokens')
                    ->trueLabel('Expired tokens')
                    ->falseLabel('Active tokens')
                    ->queries(
                        true: fn (Builder $query) => $query->where('expires_at', '<', now()),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        }),
                    ),

                Tables\Filters\TernaryFilter::make('recently_used')
                    ->label('Usage')
                    ->placeholder('All tokens')
                    ->trueLabel('Recently used')
                    ->falseLabel('Not recently used')
                    ->queries(
                        true: fn (Builder $query) => $query->where('last_used_at', '>=', now()->subDays(30)),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereNull('last_used_at')->orWhere('last_used_at', '<', now()->subDays(30));
                        }),
                    ),

                Tables\Filters\SelectFilter::make('abilities')
                    ->label('Permissions')
                    ->multiple()
                    ->options([
                        'read' => 'Read',
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'manage-users' => 'Manage Users',
                        'manage-settings' => 'Manage Settings',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) return $query;
                        
                        return $query->where(function ($q) use ($data) {
                            foreach ($data['values'] as $ability) {
                                $q->orWhereJsonContains('abilities', $ability);
                            }
                        });
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create API Token')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Create New API Token')
                    ->successNotificationTitle('API token created')
                    ->after(function ($record) {
                        // Log token creation
                        \Log::info('API token created', [
                            'token_id' => $record->id,
                            'token_name' => $record->name,
                            'user_id' => $record->tokenable_id,
                            'created_by' => auth()->id(),
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate API Token')
                    ->modalDescription('Are you sure you want to regenerate this token? The old token will be invalidated.')
                    ->action(function ($record) {
                        // Create new token with same properties
                        $newToken = $record->tokenable->createToken(
                            $record->name,
                            $record->abilities ?? []
                        );
                        
                        // Delete old token
                        $record->delete();
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Token regenerated')
                            ->body('The API token has been regenerated successfully.')
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API Token')
                    ->modalDescription('Are you sure you want to revoke this API token? This action cannot be undone.')
                    ->successNotificationTitle('API token revoked'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Revoke Selected')
                        ->modalHeading('Revoke API Tokens')
                        ->modalDescription('Are you sure you want to revoke the selected API tokens?')
                        ->successNotificationTitle('API tokens revoked'),
                ]),
            ])
            ->emptyStateHeading('No API Tokens')
            ->emptyStateDescription('This user has not created any API tokens.')
            ->emptyStateIcon('heroicon-o-key')
            ->defaultSort('created_at', 'desc');
    }
}
