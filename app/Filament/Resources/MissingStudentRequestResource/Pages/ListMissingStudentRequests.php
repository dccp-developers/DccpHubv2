<?php

namespace App\Filament\Resources\MissingStudentRequestResource\Pages;

use App\Filament\Resources\MissingStudentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissingStudentRequests extends ListRecords
{
    protected static string $resource = MissingStudentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
