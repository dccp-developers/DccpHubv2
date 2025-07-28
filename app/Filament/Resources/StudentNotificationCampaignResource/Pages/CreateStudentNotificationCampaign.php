<?php

namespace App\Filament\Resources\StudentNotificationCampaignResource\Pages;

use App\Filament\Resources\StudentNotificationCampaignResource;
use App\Enums\NotificationStatus;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStudentNotificationCampaign extends CreateRecord
{
    protected static string $resource = StudentNotificationCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = NotificationStatus::DRAFT;

        return $data;
    }
}
