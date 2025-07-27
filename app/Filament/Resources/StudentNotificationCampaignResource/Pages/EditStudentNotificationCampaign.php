<?php

namespace App\Filament\Resources\StudentNotificationCampaignResource\Pages;

use App\Filament\Resources\StudentNotificationCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentNotificationCampaign extends EditRecord
{
    protected static string $resource = StudentNotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
