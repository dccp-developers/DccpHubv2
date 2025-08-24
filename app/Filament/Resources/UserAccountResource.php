<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\User;
use App\Enums\UserRole;

use App\Enums\PersonType;
use App\Services\UserAccountService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\UserAccountResource\Pages;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class UserAccountResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User Accounts';

    protected static ?string $modelLabel = 'User Account';

    protected static ?string $pluralModelLabel = 'User Accounts';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Core user account details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('username')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->alphaDash()
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                    ]),

                Section::make('Account Settings')
                    ->description('Role, status, and access configuration')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('role')
                                    ->options(UserRole::getOptions())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $role = UserRole::from($state);
                                            $defaultPersonType = $role->getDefaultPersonType();
                                            if ($defaultPersonType) {
                                                // Convert model class to PersonType enum value
                                                $personType = PersonType::fromModelClass($defaultPersonType);
                                                if ($personType) {
                                                    $set('person_type', $personType->value);
                                                }
                                            }
                                        }
                                    }),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive users cannot log in'),

                                Forms\Components\DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->displayFormat('M j, Y g:i A')
                                    ->helperText('Leave empty if email is not verified'),
                            ]),
                    ]),

                Section::make('Person Linking')
                    ->description('Link this account to a person record')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('person_type')
                                    ->options(PersonType::getOptions())
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('person_id', null)),

                                Forms\Components\TextInput::make('person_id')
                                    ->label('Person ID')
                                    ->helperText(function (Forms\Get $get) {
                                        $personType = $get('person_type');
                                        if (!$personType) return 'Select person type first';
                                        
                                        $type = PersonType::from($personType);
                                        return match ($type) {
                                            PersonType::STUDENT => 'Enter student ID number',
                                            PersonType::FACULTY => 'Enter faculty code',
                                            PersonType::SHS_STUDENT => 'Enter student LRN',
                                            default => 'Enter identifier',
                                        };
                                    }),
                            ]),
                    ])
                    ->visible(fn (Forms\Get $get) => in_array($get('role'), ['student', 'faculty'])),

                Section::make('Security')
                    ->description('Password and security settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->helperText('Leave empty to keep current password'),

                                Forms\Components\Toggle::make('two_factor_enabled')
                                    ->label('Two-Factor Authentication')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Managed by user in their profile'),
                            ]),
                    ]),

                Section::make('Additional Information')
                    ->description('Profile and metadata')
                    ->schema([
                        Forms\Components\FileUpload::make('profile_photo_path')
                            ->label('Profile Photo')
                            ->image()
                            ->disk('public')
                            ->directory('profile-photos')
                            ->visibility('public')
                            ->imageEditor()
                            ->circleCropper(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_photo_path')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied to clipboard'),

                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    })
                    ->icon(function (string $state): string {
                        return match ($state) {
                            'admin' => 'heroicon-o-shield-check',
                            'faculty' => 'heroicon-o-user-group',
                            'student' => 'heroicon-o-academic-cap',
                            'guest' => 'heroicon-o-user',
                            'staff' => 'heroicon-o-briefcase',
                            default => 'heroicon-o-user',
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

                Tables\Columns\TextColumn::make('person_type')
                    ->label('Person Type')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'None';
                        try {
                            return PersonType::from($state)->getLabel();
                        } catch (\ValueError) {
                            return 'Unknown';
                        }
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        try {
                            return PersonType::from($state)->getColor();
                        } catch (\ValueError) {
                            return 'gray';
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(UserRole::getOptions())
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Account Status')
                    ->placeholder('All accounts')
                    ->trueLabel('Active accounts')
                    ->falseLabel('Inactive accounts'),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->placeholder('All accounts')
                    ->trueLabel('Verified emails')
                    ->falseLabel('Unverified emails')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),

                SelectFilter::make('person_type')
                    ->label('Person Type')
                    ->options(PersonType::getOptions())
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->is_active ? 'Deactivate User' : 'Activate User')
                        ->modalDescription(fn ($record) => $record->is_active
                            ? 'Are you sure you want to deactivate this user account?'
                            : 'Are you sure you want to activate this user account?')
                        ->action(function ($record) {
                            $service = app(UserAccountService::class);
                            $success = $record->is_active
                                ? $service->deactivateAccount($record)
                                : $service->activateAccount($record);

                            if ($success) {
                                Notification::make()
                                    ->success()
                                    ->title($record->is_active ? 'Account activated' : 'Account deactivated')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Action failed')
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Password')
                        ->modalDescription('Generate a new password for this user and send it via email.')
                        ->action(function ($record) {
                            $service = app(UserAccountService::class);
                            $newPassword = $service->resetPassword($record, true);

                            if ($newPassword) {
                                Notification::make()
                                    ->success()
                                    ->title('Password reset')
                                    ->body('New password generated and sent to user.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Password reset failed')
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('send_verification')
                        ->label('Send Verification')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->visible(fn ($record) => is_null($record->email_verified_at))
                        ->action(function ($record) {
                            $service = app(UserAccountService::class);
                            if ($service->sendEmailVerification($record)) {
                                Notification::make()
                                    ->success()
                                    ->title('Verification email sent')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed to send verification')
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('impersonate')
                        ->label('Impersonate')
                        ->icon('heroicon-o-user-circle')
                        ->color('gray')
                        ->visible(function ($record) {
                            $currentUser = Auth::check() ? Auth::user() : null;
                            return $currentUser &&
                                   $currentUser->role === 'admin' &&
                                   $record->id !== $currentUser->id &&
                                   $record->is_active;
                        })
                        ->url(fn ($record) => route('impersonate', $record->id), shouldOpenInNewTab: true),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Selected Users')
                        ->modalDescription('Are you sure you want to activate the selected user accounts?')
                        ->action(function (Collection $records) {
                            $service = app(UserAccountService::class);
                            $userIds = $records->pluck('id')->toArray();
                            $results = $service->bulkActivateUsers($userIds);

                            Notification::make()
                                ->success()
                                ->title('Bulk activation completed')
                                ->body("Successfully activated {$results['success']} users. Failed: {$results['failed']}")
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Selected Users')
                        ->modalDescription('Are you sure you want to deactivate the selected user accounts?')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for deactivation')
                                ->placeholder('Optional reason for deactivating these accounts')
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $service = app(UserAccountService::class);
                            $userIds = $records->pluck('id')->toArray();
                            $results = $service->bulkDeactivateUsers($userIds, $data['reason'] ?? null);

                            Notification::make()
                                ->success()
                                ->title('Bulk deactivation completed')
                                ->body("Successfully deactivated {$results['success']} users. Failed: {$results['failed']}")
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('send_verification')
                        ->label('Send Email Verification')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Send Email Verification')
                        ->modalDescription('Send email verification to selected users who haven\'t verified their email.')
                        ->action(function (Collection $records) {
                            $service = app(UserAccountService::class);
                            $count = 0;

                            foreach ($records as $user) {
                                if (is_null($user->email_verified_at)) {
                                    if ($service->sendEmailVerification($user)) {
                                        $count++;
                                    }
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Email verification sent')
                                ->body("Email verification sent to {$count} users.")
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('change_role')
                        ->label('Change Role')
                        ->icon('heroicon-o-user-group')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Change User Roles')
                        ->modalDescription('Change the role for selected users.')
                        ->form([
                            Forms\Components\Select::make('new_role')
                                ->label('New Role')
                                ->options(UserRole::getOptions())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $service = app(UserAccountService::class);
                            $newRole = UserRole::from($data['new_role']);
                            $count = 0;

                            foreach ($records as $user) {
                                if ($service->changeUserRole($user, $newRole)) {
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Role changes completed')
                                ->body("Successfully changed role for {$count} users.")
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Users')
                        ->modalDescription('Are you sure you want to delete the selected user accounts? This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Account Overview')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\ImageEntry::make('profile_photo_path')
                                    ->label('Profile Photo')
                                    ->circular()
                                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),

                                Infolists\Components\TextEntry::make('name')
                                    ->weight(FontWeight::Bold)
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                Infolists\Components\TextEntry::make('email')
                                    ->copyable()
                                    ->icon('heroicon-m-envelope'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Account Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('username')
                                    ->placeholder('Not set'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->placeholder('Not set')
                                    ->icon('heroicon-m-phone'),

                                Infolists\Components\TextEntry::make('role')
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

                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Account Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Infolists\Components\TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime()
                                    ->placeholder('Not verified')
                                    ->icon('heroicon-m-check-badge'),

                                Infolists\Components\TextEntry::make('person_type')
                                    ->label('Person Type')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return 'None';
                                        try {
                                            return PersonType::from($state)->getLabel();
                                        } catch (\ValueError) {
                                            return 'Unknown';
                                        }
                                    })
                                    ->badge()
                                    ->color(function ($state) {
                                        if (!$state) return 'gray';
                                        try {
                                            return PersonType::from($state)->getColor();
                                        } catch (\ValueError) {
                                            return 'gray';
                                        }
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Security Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('two_factor_secret')
                                    ->label('Two-Factor Authentication')
                                    ->boolean()
                                    ->getStateUsing(fn ($record) => !is_null($record->two_factor_secret))
                                    ->trueIcon('heroicon-o-shield-check')
                                    ->falseIcon('heroicon-o-shield-exclamation')
                                    ->trueColor('success')
                                    ->falseColor('gray'),

                                Infolists\Components\TextEntry::make('tokens_count')
                                    ->label('Active API Tokens')
                                    ->getStateUsing(fn ($record) => $record->tokens()->count())
                                    ->icon('heroicon-m-key'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->icon('heroicon-m-clock'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UserAccountResource\RelationManagers\OauthConnectionsRelationManager::class,
            UserAccountResource\RelationManagers\ApiTokensRelationManager::class,
            UserAccountResource\RelationManagers\FacultyNotificationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserAccounts::route('/'),
            'create' => Pages\CreateUserAccount::route('/create'),
            'view' => Pages\ViewUserAccount::route('/{record}'),
            'edit' => Pages\EditUserAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['person']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'username'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Role' => UserRole::from($record->role)->getLabel(),
            'Email' => $record->email,
            'Status' => $record->is_active ? 'Active' : 'Inactive',
        ];
    }
}
