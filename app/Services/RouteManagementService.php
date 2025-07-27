<?php

namespace App\Services;

use App\Models\Route;
use App\Models\User;
use App\Enums\RouteStatus;
use App\Enums\RouteType;
use App\Enums\NotificationType;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route as LaravelRoute;
use Illuminate\Support\Facades\Log;

class RouteManagementService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get navigation routes for a specific type and platform.
     */
    public function getNavigationRoutes(RouteType $type, bool $mobileOnly = false): Collection
    {
        return Route::getNavigationRoutes($type, $mobileOnly);
    }

    /**
     * Check if a route is accessible.
     */
    public function isRouteAccessible(string $routeName): bool
    {
        $route = $this->getRouteByName($routeName);
        
        if (!$route) {
            return true; // If route not managed, allow access
        }

        return $route->isAccessible();
    }

    /**
     * Get route by name.
     */
    public function getRouteByName(string $routeName): ?Route
    {
        return Cache::remember("route_{$routeName}", 3600, function () use ($routeName) {
            return Route::where('name', $routeName)->first();
        });
    }

    /**
     * Get route status information.
     */
    public function getRouteStatusInfo(string $routeName): array
    {
        $route = $this->getRouteByName($routeName);
        
        if (!$route) {
            return [
                'accessible' => true,
                'status' => RouteStatus::ACTIVE,
                'message' => '',
                'title' => '',
            ];
        }

        return [
            'accessible' => $route->isAccessible(),
            'status' => $route->status,
            'message' => $route->getStatusMessage(),
            'title' => $route->status->getModalTitle(),
        ];
    }

    /**
     * Update route status.
     */
    public function updateRouteStatus(Route $route, RouteStatus $status, ?User $user = null, ?string $message = null): bool
    {
        $oldStatus = $route->status;
        
        $route->update([
            'status' => $status,
            'updated_by' => $user?->id,
            'disabled_at' => $status === RouteStatus::ACTIVE ? null : now(),
            'enabled_at' => $status === RouteStatus::ACTIVE ? now() : null,
        ]);

        // Update custom message if provided
        if ($message) {
            $messageField = match ($status) {
                RouteStatus::DISABLED => 'disabled_message',
                RouteStatus::MAINTENANCE => 'maintenance_message',
                RouteStatus::DEVELOPMENT => 'development_message',
                default => null,
            };

            if ($messageField) {
                $route->update([$messageField => $message]);
            }
        }

        // Clear cache
        $this->clearRouteCache($route->name);

        // Send notifications if status changed
        if ($oldStatus !== $status) {
            $this->notifyRouteStatusChange($route, $oldStatus, $status, $user);
        }

        return true;
    }

    /**
     * Bulk update route statuses.
     */
    public function bulkUpdateRouteStatus(Collection $routes, RouteStatus $status, ?User $user = null): array
    {
        $results = ['success' => 0, 'failed' => 0];

        foreach ($routes as $route) {
            try {
                $this->updateRouteStatus($route, $status, $user);
                $results['success']++;
            } catch (\Exception $e) {
                Log::error("Failed to update route {$route->name}: " . $e->getMessage());
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Create or update route from Laravel route definition.
     */
    public function syncRouteFromLaravel(string $routeName, array $routeData, ?User $user = null): Route
    {
        $route = Route::where('name', $routeName)->first();

        $data = [
            'name' => $routeName,
            'display_name' => $routeData['display_name'] ?? $this->generateDisplayName($routeName),
            'path' => $routeData['uri'] ?? '',
            'method' => implode('|', $routeData['methods'] ?? ['GET']),
            'controller' => $routeData['controller'] ?? null,
            'action' => $routeData['action'] ?? null,
            'type' => $this->determineRouteType($routeName, $routeData),
            'status' => $routeData['status'] ?? RouteStatus::ACTIVE,
            'description' => $routeData['description'] ?? null,
            'icon' => $routeData['icon'] ?? null,
            'is_navigation' => $routeData['is_navigation'] ?? false,
            'middleware' => $routeData['middleware'] ?? [],
            'updated_by' => $user?->id,
        ];

        if ($route) {
            $route->update($data);
        } else {
            $data['created_by'] = $user?->id;
            $route = Route::create($data);
        }

        $this->clearRouteCache($routeName);

        return $route;
    }

    /**
     * Sync all Laravel routes.
     */
    public function syncAllRoutes(?User $user = null): array
    {
        $laravelRoutes = $this->getAllLaravelRoutes();
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($laravelRoutes as $routeName => $routeData) {
            try {
                $existingRoute = Route::where('name', $routeName)->first();
                
                if ($existingRoute) {
                    $this->syncRouteFromLaravel($routeName, $routeData, $user);
                    $results['updated']++;
                } else {
                    $this->syncRouteFromLaravel($routeName, $routeData, $user);
                    $results['created']++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync route {$routeName}: " . $e->getMessage());
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Clear route cache.
     */
    public function clearRouteCache(?string $routeName = null): void
    {
        if ($routeName) {
            Cache::forget("route_{$routeName}");
        }

        Route::clearNavigationCache();
    }

    /**
     * Get all Laravel routes.
     */
    private function getAllLaravelRoutes(): array
    {
        $routes = [];
        
        foreach (LaravelRoute::getRoutes() as $route) {
            $name = $route->getName();
            if ($name) {
                $routes[$name] = [
                    'uri' => $route->uri(),
                    'methods' => $route->methods(),
                    'controller' => $route->getControllerClass(),
                    'action' => $route->getActionMethod(),
                    'middleware' => $route->middleware(),
                ];
            }
        }

        return $routes;
    }

    /**
     * Determine route type based on route name and data.
     */
    private function determineRouteType(string $routeName, array $routeData): RouteType
    {
        // Check route name patterns
        if (str_starts_with($routeName, 'faculty.')) {
            return RouteType::FACULTY;
        }
        
        if (str_starts_with($routeName, 'admin.') || str_starts_with($routeName, 'filament.')) {
            return RouteType::ADMIN;
        }
        
        if (str_starts_with($routeName, 'student.')) {
            return RouteType::STUDENT;
        }
        
        if (str_starts_with($routeName, 'api.')) {
            return RouteType::API;
        }
        
        if (str_starts_with($routeName, 'enroll')) {
            return RouteType::ENROLLMENT;
        }

        // Check middleware
        $middleware = $routeData['middleware'] ?? [];
        if (in_array('auth', $middleware) || in_array('auth:sanctum', $middleware)) {
            return RouteType::STUDENT; // Default authenticated route
        }

        return RouteType::PUBLIC;
    }

    /**
     * Generate display name from route name.
     */
    private function generateDisplayName(string $routeName): string
    {
        return str($routeName)
            ->replace('.', ' ')
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /**
     * Notify users about route status changes.
     */
    private function notifyRouteStatusChange(Route $route, RouteStatus $oldStatus, RouteStatus $newStatus, ?User $user): void
    {
        // Determine which users to notify based on route type
        $users = $this->getUsersToNotify($route->type);

        if ($users->isEmpty()) {
            return;
        }

        $title = "Route Status Changed: {$route->display_name}";
        $message = "The route '{$route->display_name}' status has been changed from {$oldStatus->getLabel()} to {$newStatus->getLabel()}.";
        
        if ($newStatus !== RouteStatus::ACTIVE) {
            $message .= " " . $route->getStatusMessage();
        }

        $this->notificationService->sendToUsers(
            $users,
            $title,
            $message,
            'info',
            [
                'route_name' => $route->name,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'changed_by' => $user?->name,
            ],
            $route->url,
            'View Route',
            'normal'
        );
    }

    /**
     * Get users to notify based on route type.
     */
    private function getUsersToNotify(RouteType $routeType): Collection
    {
        return match ($routeType) {
            RouteType::FACULTY => User::where('role', 'faculty')->get(),
            RouteType::STUDENT => User::where('role', 'student')->get(),
            RouteType::ADMIN => User::where('role', 'admin')->get(),
            default => collect(),
        };
    }
}
