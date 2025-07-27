<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\Pages;

use App\Filament\Resources\UserAccountResource;
use App\Services\UserAccountService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUserAccount extends EditRecord
{
    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User account updated')
            ->body('The user account has been updated successfully.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Track changes for logging
        $this->trackChanges($data);

        return $data;
    }

    protected function afterSave(): void
    {
        // Log the update
        \Log::info('User account updated', [
            'user_id' => $this->record->id,
            'updated_by' => auth()->id(),
            'changes' => $this->record->getChanges(),
        ]);
    }

    private function trackChanges(array $data): void
    {
        $original = $this->record->getOriginal();
        $changes = [];

        foreach ($data as $key => $value) {
            if (isset($original[$key]) && $original[$key] !== $value) {
                $changes[$key] = [
                    'from' => $original[$key],
                    'to' => $value,
                ];
            }
        }

        if (!empty($changes)) {
            \Log::info('User account changes detected', [
                'user_id' => $this->record->id,
                'changes' => $changes,
                'updated_by' => auth()->id(),
            ]);
        }
    }
}
