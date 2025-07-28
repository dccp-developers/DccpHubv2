<?php

namespace App\Filament\Resources\StudentNotificationCampaignResource\Pages;

use App\Filament\Resources\StudentNotificationCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditStudentNotificationCampaign extends EditRecord
{
    protected static string $resource = StudentNotificationCampaignResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
