<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\Pages;

use App\Filament\Resources\UserAccountResource;
use App\Services\UserAccountService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUserAccount extends CreateRecord
{
    protected static string $resource = UserAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User account created')
            ->body('The user account has been created successfully.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        
        // If email is not verified, set it to null
        if (!isset($data['email_verified_at'])) {
            $data['email_verified_at'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        
        // Send welcome email if account is active and email is verified
        if ($user->is_active && $user->email_verified_at) {
            // Implement welcome email logic here
            // app(UserAccountService::class)->sendWelcomeEmail($user);
        }
    }
}
