<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouteResource\Pages;

use App\Models\Route;
use App\Models\User;
use App\Enums\RouteStatus;
use App\Enums\RouteType;
use App\Services\RouteManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'System Management';

    protected static ?string $navigationLabel = 'Routes & Navigation';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Route Configuration')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Route Name')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('e.g., faculty.dashboard')
                                            ->helperText('Laravel route name (must be unique)'),

                                        Forms\Components\TextInput::make('display_name')
                                            ->label('Display Name')
                                            ->required()
                                            ->placeholder('e.g., Faculty Dashboard')
                                            ->helperText('Human-readable name for navigation'),

                                        Forms\Components\TextInput::make('path')
                                            ->label('Path/URL')
                                            ->required()
                                            ->placeholder('e.g., /faculty/dashboard')
                                            ->helperText('URL path or external URL'),

                                        Forms\Components\Select::make('method')
                                            ->label('HTTP Method')
                                            ->options([
                                                'GET' => 'GET',
                                                'POST' => 'POST',
                                                'PUT' => 'PUT',
                                                'PATCH' => 'PATCH',
                                                'DELETE' => 'DELETE',
                                                'GET|POST' => 'GET|POST',
                                            ])
                                            ->default('GET')
                                            ->required(),

                                        Forms\Components\Select::make('type')
                                            ->label('Route Type')
                                            ->options(RouteType::getOptions())
                                            ->required()
                                            ->reactive()
                                            ->helperText('Determines access level and layout'),

                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->options(RouteStatus::getOptions())
                                            ->default(RouteStatus::ACTIVE)
                                            ->required()
                                            ->reactive(),
                                    ]),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->placeholder('Brief description of what this route does'),

                                Forms\Components\TextInput::make('icon')
                                    ->label('Icon')
                                    ->placeholder('e.g., heroicon-o-home')
                                    ->helperText('Icon for navigation (Heroicons format)'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Navigation & Visibility')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_navigation')
                                            ->label('Show in Navigation')
                                            ->helperText('Display in sidebar/menu'),

                                        Forms\Components\Toggle::make('is_mobile_visible')
                                            ->label('Mobile Visible')
                                            ->default(true)
                                            ->helperText('Show on mobile devices'),

                                        Forms\Components\Toggle::make('is_desktop_visible')
                                            ->label('Desktop Visible')
                                            ->default(true)
                                            ->helperText('Show on desktop'),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Sort Order')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Lower numbers appear first'),

                                        Forms\Components\Select::make('parent_id')
                                            ->label('Parent Route')
                                            ->relationship('parent', 'display_name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('For sub-navigation items'),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_external')
                                            ->label('External Link')
                                            ->reactive()
                                            ->helperText('Link to external website'),

                                        Forms\Components\Select::make('target')
                                            ->label('Link Target')
                                            ->options([
                                                '_self' => 'Same Window',
                                                '_blank' => 'New Window',
                                                '_parent' => 'Parent Frame',
                                                '_top' => 'Top Frame',
                                            ])
                                            ->default('_self')
                                            ->visible(fn (Forms\Get $get) => $get('is_external')),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Technical Configuration')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('controller')
                                            ->label('Controller')
                                            ->placeholder('e.g., App\Http\Controllers\DashboardController')
                                            ->helperText('Full controller class name'),

                                        Forms\Components\TextInput::make('action')
                                            ->label('Action/Method')
                                            ->placeholder('e.g., index')
                                            ->helperText('Controller method name'),
                                    ]),

                                Forms\Components\TagsInput::make('middleware')
                                    ->label('Middleware')
                                    ->placeholder('Add middleware...')
                                    ->helperText('Laravel middleware (e.g., auth, role:admin)'),

                                Forms\Components\KeyValue::make('parameters')
                                    ->label('Default Parameters')
                                    ->keyLabel('Parameter')
                                    ->valueLabel('Value')
                                    ->helperText('Default route parameters'),

                                Forms\Components\TagsInput::make('required_permissions')
                                    ->label('Required Permissions')
                                    ->placeholder('Add permission...')
                                    ->helperText('Permissions required to access this route'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Status Messages')
                            ->schema([
                                Forms\Components\Textarea::make('disabled_message')
                                    ->label('Disabled Message')
                                    ->rows(3)
                                    ->placeholder('Custom message when route is disabled')
                                    ->visible(fn (Forms\Get $get) => $get('status') === RouteStatus::DISABLED->value),

                                Forms\Components\Textarea::make('maintenance_message')
                                    ->label('Maintenance Message')
                                    ->rows(3)
                                    ->placeholder('Custom message when route is under maintenance')
                                    ->visible(fn (Forms\Get $get) => $get('status') === RouteStatus::MAINTENANCE->value),

                                Forms\Components\Textarea::make('development_message')
                                    ->label('Development Message')
                                    ->rows(3)
                                    ->placeholder('Custom message when route is in development')
                                    ->visible(fn (Forms\Get $get) => $get('status') === RouteStatus::DEVELOPMENT->value),

                                Forms\Components\TextInput::make('redirect_url')
                                    ->label('Redirect URL')
                                    ->url()
                                    ->placeholder('https://example.com')
                                    ->helperText('Custom redirect URL (overrides default route)'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Metadata')
                            ->schema([
                                Forms\Components\KeyValue::make('metadata')
                                    ->label('Additional Metadata')
                                    ->keyLabel('Key')
                                    ->valueLabel('Value')
                                    ->helperText('Additional data for custom functionality'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('path')
                    ->label('Path')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (RouteType $state): string => $state->getLabel())
                    ->badge()
                    ->color(fn (RouteType $state): string => match ($state) {
                        RouteType::STUDENT => 'primary',
                        RouteType::FACULTY => 'success',
                        RouteType::ADMIN => 'danger',
                        RouteType::PUBLIC => 'gray',
                        RouteType::API => 'info',
                        RouteType::GUEST => 'warning',
                        RouteType::ENROLLMENT => 'purple',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (RouteStatus $state): string => $state->getLabel())
                    ->badge()
                    ->color(fn (RouteStatus $state): string => match ($state) {
                        RouteStatus::ACTIVE => 'success',
                        RouteStatus::DISABLED => 'danger',
                        RouteStatus::MAINTENANCE => 'warning',
                        RouteStatus::DEVELOPMENT => 'info',
                    }),

                Tables\Columns\IconColumn::make('is_navigation')
                    ->label('Nav')
                    ->boolean()
                    ->tooltip('Show in Navigation'),

                Tables\Columns\IconColumn::make('is_mobile_visible')
                    ->label('Mobile')
                    ->boolean()
                    ->tooltip('Mobile Visible'),

                Tables\Columns\IconColumn::make('is_desktop_visible')
                    ->label('Desktop')
                    ->boolean()
                    ->tooltip('Desktop Visible'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Route Type')
                    ->options(RouteType::getOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(RouteStatus::getOptions()),

                Tables\Filters\TernaryFilter::make('is_navigation')
                    ->label('Navigation')
                    ->placeholder('All routes')
                    ->trueLabel('Navigation only')
                    ->falseLabel('Non-navigation only'),

                Tables\Filters\TernaryFilter::make('is_mobile_visible')
                    ->label('Mobile Visible'),

                Tables\Filters\TernaryFilter::make('is_desktop_visible')
                    ->label('Desktop Visible'),

                Tables\Filters\Filter::make('parent_routes')
                    ->label('Parent Routes Only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Route $record): bool => $record->status !== RouteStatus::ACTIVE)
                        ->requiresConfirmation()
                        ->action(function (Route $record): void {
                            $service = app(RouteManagementService::class);
                            $service->updateRouteStatus($record, RouteStatus::ACTIVE, Auth::user());

                            Notification::make()
                                ->title('Route Activated')
                                ->body("Route '{$record->display_name}' has been activated.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('disable')
                        ->label('Disable')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Route $record): bool => $record->status === RouteStatus::ACTIVE)
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('message')
                                ->label('Disabled Message')
                                ->placeholder('Optional custom message for users')
                                ->rows(3),
                        ])
                        ->action(function (Route $record, array $data): void {
                            $service = app(RouteManagementService::class);
                            $service->updateRouteStatus(
                                $record,
                                RouteStatus::DISABLED,
                                Auth::user(),
                                $data['message'] ?? null
                            );

                            Notification::make()
                                ->title('Route Disabled')
                                ->body("Route '{$record->display_name}' has been disabled.")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('maintenance')
                        ->label('Maintenance')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->color('warning')
                        ->visible(fn (Route $record): bool => $record->status !== RouteStatus::MAINTENANCE)
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('message')
                                ->label('Maintenance Message')
                                ->placeholder('Optional custom message for users')
                                ->rows(3),
                        ])
                        ->action(function (Route $record, array $data): void {
                            $service = app(RouteManagementService::class);
                            $service->updateRouteStatus(
                                $record,
                                RouteStatus::MAINTENANCE,
                                Auth::user(),
                                $data['message'] ?? null
                            );

                            Notification::make()
                                ->title('Route Under Maintenance')
                                ->body("Route '{$record->display_name}' is now under maintenance.")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(RouteManagementService::class);
                            $results = $service->bulkUpdateRouteStatus($records, RouteStatus::ACTIVE, Auth::user());

                            Notification::make()
                                ->title('Bulk Activation Complete')
                                ->body("Activated {$results['success']} routes. Failed: {$results['failed']}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('disable')
                        ->label('Disable Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(RouteManagementService::class);
                            $results = $service->bulkUpdateRouteStatus($records, RouteStatus::DISABLED, Auth::user());

                            Notification::make()
                                ->title('Bulk Disable Complete')
                                ->body("Disabled {$results['success']} routes. Failed: {$results['failed']}")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('maintenance')
                        ->label('Set Maintenance')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(RouteManagementService::class);
                            $results = $service->bulkUpdateRouteStatus($records, RouteStatus::MAINTENANCE, Auth::user());

                            Notification::make()
                                ->title('Bulk Maintenance Complete')
                                ->body("Set {$results['success']} routes to maintenance. Failed: {$results['failed']}")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ])
            ])
            ->defaultSort('sort_order')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['creator', 'parent']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'display_name', 'path', 'description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Type' => $record->type->getLabel(),
            'Status' => $record->status->getLabel(),
            'Path' => $record->path,
        ];
    }
}
