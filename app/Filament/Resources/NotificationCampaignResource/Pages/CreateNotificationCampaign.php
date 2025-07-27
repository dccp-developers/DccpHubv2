<?php

namespace App\Filament\Resources\NotificationCampaignResource\Pages;

use App\Filament\Resources\NotificationCampaignResource;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotificationCampaign extends CreateRecord
{
    protected static string $resource = NotificationCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = NotificationStatus::DRAFT;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_from_template')
                ->label('Create from Template')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('template_type')
                        ->label('Select Template')
                        ->options(NotificationType::getOptions())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $type = NotificationType::from($data['template_type']);
                    $template = $type->getTemplate();

                    $this->form->fill([
                        'type' => $type->value,
                        'priority' => $type->getDefaultPriority()->value,
                        'notification_title' => $template['title'],
                        'notification_message' => $template['message'],
                        'action_text' => $template['action_text'],
                        'action_url' => $template['action_url'],
                        'title' => $type->getLabel() . ' Campaign',
                        'description' => $type->getDescription(),
                    ]);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
