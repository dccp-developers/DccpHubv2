<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentNotificationCampaignResource\Pages;
use App\Models\StudentNotificationCampaign;
use App\Models\User;
use App\Enums\NotificationType;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Services\StudentNotificationCampaignService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StudentNotificationCampaignResource extends Resource
{
    protected static ?string $model = StudentNotificationCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campaign Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->options(NotificationType::getOptions())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $type = NotificationType::from($state);
                                    $template = $type->getTemplate();

                                    $set('notification_title', $template['title']);
                                    $set('notification_message', $template['message']);
                                    $set('action_text', $template['action_text']);
                                    $set('action_url', $template['action_url']);
                                    $set('priority', $type->getDefaultPriority()->value);
                                }
                            }),

                        Forms\Components\Select::make('priority')
                            ->options(NotificationPriority::getOptions())
                            ->required()
                            ->default('normal'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notification Content')
                    ->schema([
                        Forms\Components\TextInput::make('notification_title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notification_message')
                            ->label('Message')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('action_text')
                            ->label('Action Button Text')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('action_url')
                            ->label('Action URL')
                            ->maxLength(255)
                            ->helperText('Enter a relative path (e.g., /dashboard) or full URL')
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if ($value && !empty($value)) {
                                        // Allow relative paths (starting with /) or full URLs
                                        if (!str_starts_with($value, '/') && !filter_var($value, FILTER_VALIDATE_URL)) {
                                            $fail('The action URL must be a valid URL or relative path starting with /');
                                        }
                                    }
                                };
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recipients')
                    ->schema([
                        Forms\Components\Toggle::make('send_to_all_students')
                            ->label('Send to All Students')
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('recipient_ids')
                            ->label('Specific Students')
                            ->multiple()
                            ->searchable()
                            ->options(User::where('role', 'student')->pluck('name', 'id'))
                            ->hidden(fn (callable $get) => $get('send_to_all_students'))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule For')
                            ->helperText('Leave empty to send immediately')
                            ->minDate(now()),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('When should this notification expire?')
                            ->minDate(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        NotificationType::ENROLLMENT_APPROVED => 'success',
                        NotificationType::ENROLLMENT_REJECTED => 'danger',
                        NotificationType::TUITION_DUE => 'warning',
                        NotificationType::TUITION_OVERDUE => 'danger',
                        NotificationType::ACADEMIC_WARNING => 'danger',
                        NotificationType::GENERAL_ANNOUNCEMENT => 'primary',
                        default => 'primary'
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),

                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),

                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(NotificationType::getOptions()),

                Tables\Filters\SelectFilter::make('priority')
                    ->options(NotificationPriority::getOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(NotificationStatus::getOptions()),

                Tables\Filters\Filter::make('scheduled')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('scheduled_at')),

                Tables\Filters\Filter::make('sent_today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('sent_at', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (StudentNotificationCampaign $record) => $record->status === NotificationStatus::DRAFT || $record->status === NotificationStatus::SCHEDULED)
                    ->action(function (StudentNotificationCampaign $record) {
                        $service = app(StudentNotificationCampaignService::class);

                        if ($record->status === NotificationStatus::DRAFT) {
                            $record->update(['status' => NotificationStatus::SCHEDULED]);
                        }

                        if ($service->sendCampaign($record)) {
                            Notification::make()
                                ->title('Campaign sent successfully!')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to send campaign')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (StudentNotificationCampaign $record) {
                        $service = app(StudentNotificationCampaignService::class);
                        $newCampaign = $service->duplicateCampaign($record);

                        Notification::make()
                            ->title('Campaign duplicated successfully!')
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.resources.student-notification-campaigns.edit', $newCampaign);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('send_selected')
                        ->label('Send Selected')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $service = app(StudentNotificationCampaignService::class);
                            $sent = 0;

                            foreach ($records as $record) {
                                if ($record->status === NotificationStatus::DRAFT) {
                                    $record->update(['status' => NotificationStatus::SCHEDULED]);
                                }

                                if ($service->sendCampaign($record)) {
                                    $sent++;
                                }
                            }

                            Notification::make()
                                ->title("Sent {$sent} out of {$records->count()} campaigns")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentNotificationCampaigns::route('/'),
            'create' => Pages\CreateStudentNotificationCampaign::route('/create'),
            'edit' => Pages\EditStudentNotificationCampaign::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationGroup(): string
    {
        return 'Communication';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', NotificationStatus::DRAFT)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
