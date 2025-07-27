<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentNotificationCampaignResource\Pages;
use App\Filament\Resources\StudentNotificationCampaignResource\RelationManagers;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

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
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('action_text')
                            ->label('Action Button Text')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('action_url')
                            ->label('Action URL')
                            ->url()
                            ->maxLength(255),
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
                            ->helperText('Leave empty to send immediately'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('Optional: When this notification should expire'),
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
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('type')
                    ->formatStateUsing(fn ($state) => NotificationType::from($state)->getLabel())
                    ->colors([
                        'primary' => 'general_announcement',
                        'success' => 'enrollment_approved',
                        'warning' => 'tuition_due',
                        'danger' => 'academic_warning',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn ($state) => NotificationStatus::from($state)->getLabel())
                    ->colors([
                        'secondary' => NotificationStatus::DRAFT->value,
                        'warning' => NotificationStatus::SCHEDULED->value,
                        'success' => NotificationStatus::SENT->value,
                        'danger' => NotificationStatus::FAILED->value,
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->formatStateUsing(fn ($state) => NotificationPriority::from($state)->getLabel())
                    ->colors([
                        'secondary' => NotificationPriority::LOW->value,
                        'primary' => NotificationPriority::NORMAL->value,
                        'warning' => NotificationPriority::HIGH->value,
                        'danger' => NotificationPriority::URGENT->value,
                    ]),

                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->formatStateUsing(function ($record) {
                        if ($record->send_to_all_students) {
                            return 'All Students (' . User::where('role', 'student')->count() . ')';
                        }
                        return $record->total_recipients ?: count($record->recipient_ids ?? []);
                    }),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->status === NotificationStatus::SENT ?
                        "{$state}/{$record->total_recipients}" : '-'
                    ),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Send Now'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(NotificationStatus::getOptions()),

                Tables\Filters\SelectFilter::make('type')
                    ->options(NotificationType::getOptions()),

                Tables\Filters\SelectFilter::make('priority')
                    ->options(NotificationPriority::getOptions()),

                Tables\Filters\Filter::make('send_to_all_students')
                    ->label('Send to All Students')
                    ->query(fn (Builder $query): Builder => $query->where('send_to_all_students', true)),

                Tables\Filters\Filter::make('scheduled')
                    ->label('Scheduled')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('scheduled_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (StudentNotificationCampaign $record) =>
                        $record->status === NotificationStatus::DRAFT ||
                        $record->status === NotificationStatus::SCHEDULED
                    )
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

                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Preview Notification')
                    ->modalContent(fn (StudentNotificationCampaign $record) => view('filament.notifications.preview', [
                        'title' => $record->notification_title,
                        'message' => $record->notification_message,
                        'type' => $record->type->value,
                        'priority' => $record->priority->value,
                        'action_text' => $record->action_text,
                        'action_url' => $record->action_url,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

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
        return 'heroicon-o-academic-cap';
    }

    public static function getNavigationGroup(): string
    {
        return 'Student Communication';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', NotificationStatus::DRAFT)->count();
    }

    public static function getNavigationLabel(): string
    {
        return 'Student Notifications';
    }

    public static function getModelLabel(): string
    {
        return 'Student Notification Campaign';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Student Notification Campaigns';
    }
}
