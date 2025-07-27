<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use App\Services\RouteManagementService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ListRoutes extends ListRecords
{
    protected static string $resource = RouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('sync_routes')
                ->label('Sync Laravel Routes')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sync Laravel Routes')
                ->modalDescription('This will scan all Laravel routes and create/update route records. Existing custom settings will be preserved.')
                ->action(function (): void {
                    $service = app(RouteManagementService::class);
                    $results = $service->syncAllRoutes(Auth::user());

                    Notification::make()
                        ->title('Route Sync Complete')
                        ->body("Created: {$results['created']}, Updated: {$results['updated']}, Skipped: {$results['skipped']}")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('clear_cache')
                ->label('Clear Route Cache')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    $service = app(RouteManagementService::class);
                    $service->clearRouteCache();

                    Notification::make()
                        ->title('Cache Cleared')
                        ->body('Route cache has been cleared successfully.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
