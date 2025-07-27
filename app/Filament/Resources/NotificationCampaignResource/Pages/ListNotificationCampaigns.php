<?php

namespace App\Filament\Resources\NotificationCampaignResource\Pages;

use App\Filament\Resources\NotificationCampaignResource;
use App\Enums\NotificationType;
use App\Services\NotificationCampaignService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListNotificationCampaigns extends ListRecords
{
    protected static string $resource = NotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_create')
                ->label('Quick Create')
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

                                $set('title', $template['title']);
                                $set('message', $template['message']);
                                $set('action_text', $template['action_text']);
                                $set('action_url', $template['action_url']);
                            }
                        }),

                    \Filament\Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),

                    \Filament\Forms\Components\Textarea::make('message')
                        ->required()
                        ->maxLength(1000)
                        ->rows(3),

                    \Filament\Forms\Components\TextInput::make('action_text')
                        ->maxLength(50),

                    \Filament\Forms\Components\TextInput::make('action_url')
                        ->maxLength(255)
                        ->helperText('Enter a relative path (e.g., /faculty/classes) or full URL')
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

                    \Filament\Forms\Components\Toggle::make('send_to_all')
                        ->label('Send to All Faculty')
                        ->default(true),

                    \Filament\Forms\Components\Toggle::make('send_immediately')
                        ->label('Send Immediately')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $service = app(NotificationCampaignService::class);

                    $campaign = $service->createFromTemplate(
                        NotificationType::from($data['type']),
                        [
                            'title' => $data['type'] . ' - ' . now()->format('M d, Y'),
                            'notification_title' => $data['title'],
                            'notification_message' => $data['message'],
                            'action_text' => $data['action_text'],
                            'action_url' => $data['action_url'],
                        ],
                        $data['send_to_all'] ? null : []
                    );

                    if ($data['send_immediately']) {
                        $campaign->update(['status' => \App\Enums\NotificationStatus::SCHEDULED]);

                        if ($service->sendCampaign($campaign)) {
                            Notification::make()
                                ->title('Campaign created and sent successfully!')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Campaign created but failed to send')
                                ->warning()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Campaign created successfully!')
                            ->body('You can edit and send it later.')
                            ->success()
                            ->send();
                    }
                }),

            Actions\Action::make('process_scheduled')
                ->label('Process Scheduled')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->action(function () {
                    $service = app(NotificationCampaignService::class);
                    $processed = $service->processScheduledCampaigns();

                    Notification::make()
                        ->title("Processed {$processed} scheduled campaigns")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('Create Campaign'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            NotificationCampaignResource\Widgets\CampaignStatsWidget::class,
        ];
    }
}
