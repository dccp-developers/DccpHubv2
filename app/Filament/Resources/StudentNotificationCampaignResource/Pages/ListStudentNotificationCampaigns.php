<?php

namespace App\Filament\Resources\StudentNotificationCampaignResource\Pages;

use App\Filament\Resources\StudentNotificationCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentNotificationCampaigns extends ListRecords
{
    protected static string $resource = StudentNotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
