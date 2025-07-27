<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserAccountResource\Pages;

use App\Filament\Resources\UserAccountResource;
use App\Services\UserAccountService;
use App\Enums\UserRole;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewUserAccount extends ViewRecord
{
    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('activate')
                ->label('Activate Account')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => !$this->record->is_active)
                ->requiresConfirmation()
                ->modalHeading('Activate User Account')
                ->modalDescription('Are you sure you want to activate this user account? The user will be able to log in.')
                ->action(function () {
                    $service = app(UserAccountService::class);
                    if ($service->activateAccount($this->record)) {
                        Notification::make()
                            ->success()
                            ->title('Account activated')
                            ->body('The user account has been activated successfully.')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Activation failed')
                            ->body('Failed to activate the user account.')
                            ->send();
                    }
                }),

            Actions\Action::make('deactivate')
                ->label('Deactivate Account')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->is_active)
                ->requiresConfirmation()
                ->modalHeading('Deactivate User Account')
                ->modalDescription('Are you sure you want to deactivate this user account? The user will not be able to log in.')
                ->action(function () {
                    $service = app(UserAccountService::class);
                    if ($service->deactivateAccount($this->record)) {
                        Notification::make()
                            ->success()
                            ->title('Account deactivated')
                            ->body('The user account has been deactivated successfully.')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Deactivation failed')
                            ->body('Failed to deactivate the user account.')
                            ->send();
                    }
                }),

            Actions\Action::make('reset_password')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset User Password')
                ->modalDescription('Are you sure you want to reset this user\'s password? A new password will be generated and sent to their email.')
                ->action(function () {
                    $service = app(UserAccountService::class);
                    $newPassword = $service->resetPassword($this->record, true);
                    if ($newPassword) {
                        Notification::make()
                            ->success()
                            ->title('Password reset')
                            ->body('A new password has been generated and sent to the user\'s email.')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Password reset failed')
                            ->body('Failed to reset the user\'s password.')
                            ->send();
                    }
                }),

            Actions\Action::make('send_verification')
                ->label('Send Email Verification')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->visible(fn () => is_null($this->record->email_verified_at))
                ->action(function () {
                    $service = app(UserAccountService::class);
                    if ($service->sendEmailVerification($this->record)) {
                        Notification::make()
                            ->success()
                            ->title('Verification email sent')
                            ->body('Email verification has been sent to the user.')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Failed to send verification')
                            ->body('Failed to send email verification.')
                            ->send();
                    }
                }),

            Actions\Action::make('impersonate')
                ->label('Impersonate User')
                ->icon('heroicon-o-user-circle')
                ->color('gray')
                ->visible(function () {
                    $currentUser = auth()->user();
                    return $currentUser && 
                           $currentUser->role === 'admin' && 
                           $this->record->id !== $currentUser->id &&
                           $this->record->is_active;
                })
                ->requiresConfirmation()
                ->modalHeading('Impersonate User')
                ->modalDescription('Are you sure you want to impersonate this user? You will be logged in as them.')
                ->action(function () {
                    // Implement impersonation logic here
                    // This would typically use a package like laravel-impersonate
                    Notification::make()
                        ->info()
                        ->title('Impersonation')
                        ->body('Impersonation feature needs to be implemented.')
                        ->send();
                }),
        ];
    }
}
