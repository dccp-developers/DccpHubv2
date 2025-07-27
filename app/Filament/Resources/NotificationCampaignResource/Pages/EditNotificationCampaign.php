<?php

namespace App\Filament\Resources\NotificationCampaignResource\Pages;

use App\Filament\Resources\NotificationCampaignResource;
use App\Enums\NotificationStatus;
use App\Services\NotificationCampaignService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditNotificationCampaign extends EditRecord
{
    protected static string $resource = NotificationCampaignResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_now')
                ->label('Send Now')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === NotificationStatus::DRAFT || $this->record->status === NotificationStatus::SCHEDULED)
                ->action(function () {
                    $service = app(NotificationCampaignService::class);

                    if ($this->record->status === NotificationStatus::DRAFT) {
                        $this->record->update(['status' => NotificationStatus::SCHEDULED]);
                    }

                    if ($service->sendCampaign($this->record)) {
                        Notification::make()
                            ->title('Campaign sent successfully!')
                            ->body("Sent to {$this->record->sent_count} recipients")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to send campaign')
                            ->body('Please check the error log for details')
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Send Campaign Now')
                ->modalDescription('Are you sure you want to send this campaign immediately?'),

            Actions\Action::make('schedule')
                ->label('Schedule')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn () => $this->record->status === NotificationStatus::DRAFT)
                ->form([
                    \Filament\Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Schedule For')
                        ->required()
                        ->minDate(now()),
                ])
                ->action(function (array $data) {
                    $service = app(NotificationCampaignService::class);

                    if ($service->scheduleCampaign($this->record, new \DateTime($data['scheduled_at']))) {
                        Notification::make()
                            ->title('Campaign scheduled successfully!')
                            ->body("Scheduled for {$data['scheduled_at']}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to schedule campaign')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $service = app(NotificationCampaignService::class);
                    $newCampaign = $service->duplicateCampaign($this->record);

                    Notification::make()
                        ->title('Campaign duplicated successfully!')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.notification-campaigns.edit', $newCampaign);
                }),

            Actions\Action::make('test_send')
                ->label('Send Test')
                ->icon('heroicon-o-beaker')
                ->color('info')
                ->action(function () {
                    $service = app(\App\Services\NotificationService::class);

                    try {
                        $service->sendToUser(
                            Auth::user(),
                            '[TEST] ' . $this->record->notification_title,
                            $this->record->notification_message,
                            $this->record->type->value, // type parameter
                            $this->record->additional_data ?? [], // data parameter
                            $this->record->action_url, // actionUrl parameter
                            $this->record->action_text, // actionText parameter
                            $this->record->priority->value, // priority parameter
                            $this->record->expires_at // expiresAt parameter
                        );

                        Notification::make()
                            ->title('Test notification sent!')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to send test notification')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
