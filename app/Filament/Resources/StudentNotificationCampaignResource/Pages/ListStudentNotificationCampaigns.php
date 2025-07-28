<?php

namespace App\Filament\Resources\StudentNotificationCampaignResource\Pages;

use App\Filament\Resources\StudentNotificationCampaignResource;
use App\Enums\NotificationType;
use App\Enums\NotificationPriority;
use App\Models\User;
use App\Services\StudentNotificationCampaignService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListStudentNotificationCampaigns extends ListRecords
{
    protected static string $resource = StudentNotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_notification')
                ->label('Quick Notification')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('type')
                        ->label('Notification Type')
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

                    \Filament\Forms\Components\TextInput::make('notification_title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),

                    \Filament\Forms\Components\Textarea::make('notification_message')
                        ->label('Message')
                        ->required()
                        ->maxLength(1000)
                        ->rows(3),

                    \Filament\Forms\Components\TextInput::make('action_text')
                        ->label('Action Button Text')
                        ->maxLength(50),

                    \Filament\Forms\Components\TextInput::make('action_url')
                        ->label('Action URL')
                        ->maxLength(255),

                    \Filament\Forms\Components\Select::make('priority')
                        ->label('Priority')
                        ->options(NotificationPriority::getOptions())
                        ->required()
                        ->default('normal'),

                    \Filament\Forms\Components\Toggle::make('send_to_all_students')
                        ->label('Send to All Students')
                        ->reactive()
                        ->default(true),

                    \Filament\Forms\Components\Select::make('recipient_ids')
                        ->label('Specific Students')
                        ->multiple()
                        ->searchable()
                        ->options(User::where('role', 'student')->pluck('name', 'id'))
                        ->hidden(fn (callable $get) => $get('send_to_all_students')),

                    \Filament\Forms\Components\Toggle::make('send_email')
                        ->label('Send Email Notification')
                        ->reactive()
                        ->default(false),

                    \Filament\Forms\Components\TextInput::make('email_subject')
                        ->label('Email Subject')
                        ->maxLength(255)
                        ->visible(fn (callable $get) => $get('send_email')),

                    \Filament\Forms\Components\Textarea::make('email_message')
                        ->label('Email Message')
                        ->maxLength(1000)
                        ->rows(3)
                        ->visible(fn (callable $get) => $get('send_email')),

                    \Filament\Forms\Components\Toggle::make('send_immediately')
                        ->label('Send Immediately')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $service = app(StudentNotificationCampaignService::class);

                    $campaign = $service->createFromTemplate(
                        NotificationType::from($data['type']),
                        [
                            'title' => $data['type'] . ' - ' . now()->format('M d, Y'),
                            'notification_title' => $data['notification_title'],
                            'notification_message' => $data['notification_message'],
                            'action_text' => $data['action_text'],
                            'action_url' => $data['action_url'],
                            'priority' => $data['priority'],
                            'send_email' => $data['send_email'] ?? false,
                            'email_subject' => $data['email_subject'] ?? null,
                            'email_message' => $data['email_message'] ?? null,
                        ],
                        $data['send_to_all_students'] ? null : ($data['recipient_ids'] ?? [])
                    );

                    if ($data['send_immediately'] && $service->sendCampaign($campaign)) {
                        Notification::make()
                            ->title('Quick notification sent successfully!')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Quick notification created and scheduled!')
                            ->success()
                            ->send();
                    }
                }),

            Actions\CreateAction::make(),
        ];
    }
}
