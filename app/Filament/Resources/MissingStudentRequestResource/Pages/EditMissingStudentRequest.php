<?php

namespace App\Filament\Resources\MissingStudentRequestResource\Pages;

use App\Filament\Resources\MissingStudentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissingStudentRequest extends EditRecord
{
    protected static string $resource = MissingStudentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
