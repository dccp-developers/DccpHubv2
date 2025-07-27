<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use App\Enums\RouteStatus;
use App\Services\RouteManagementService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditRoute extends EditRecord
{
    protected static string $resource = RouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status !== RouteStatus::ACTIVE)
                ->requiresConfirmation()
                ->action(function (): void {
                    $service = app(RouteManagementService::class);
                    $service->updateRouteStatus($this->record, RouteStatus::ACTIVE, Auth::user());

                    Notification::make()
                        ->title('Route Activated')
                        ->body("Route '{$this->record->display_name}' has been activated.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('disable')
                ->label('Disable')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === RouteStatus::ACTIVE)
                ->requiresConfirmation()
                ->action(function (): void {
                    $service = app(RouteManagementService::class);
                    $service->updateRouteStatus($this->record, RouteStatus::DISABLED, Auth::user());

                    Notification::make()
                        ->title('Route Disabled')
                        ->body("Route '{$this->record->display_name}' has been disabled.")
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
