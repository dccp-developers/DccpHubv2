<?php

namespace App\Services;

use App\Models\Route;
use App\Enums\RouteType;
use App\Enums\RouteStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Get navigation items for a specific route type.
     */
    public function getNavigationItems(RouteType $type, bool $mobileOnly = false): array
    {
        $routes = Route::getNavigationRoutes($type, $mobileOnly);
        
        return $this->formatNavigationItems($routes);
    }

    /**
     * Get student navigation items.
     */
    public function getStudentNavigation(bool $mobileOnly = false): array
    {
        return $this->getNavigationItems(RouteType::STUDENT, $mobileOnly);
    }

    /**
     * Get faculty navigation items.
     */
    public function getFacultyNavigation(bool $mobileOnly = false): array
    {
        return $this->getNavigationItems(RouteType::FACULTY, $mobileOnly);
    }

    /**
     * Get admin navigation items.
     */
    public function getAdminNavigation(bool $mobileOnly = false): array
    {
        return $this->getNavigationItems(RouteType::ADMIN, $mobileOnly);
    }

    /**
     * Get public navigation items.
     */
    public function getPublicNavigation(bool $mobileOnly = false): array
    {
        return $this->getNavigationItems(RouteType::PUBLIC, $mobileOnly);
    }

    /**
     * Format routes into navigation items.
     */
    private function formatNavigationItems(Collection $routes): array
    {
        $items = [];

        foreach ($routes as $route) {
            $item = [
                'name' => $route->display_name,
                'href' => $this->getRouteUrl($route),
                'icon' => $route->icon ?: 'heroicon-o-squares-2x2',
                'current' => $this->isCurrentRoute($route),
                'route' => $route->name,
                'status' => $route->status->value,
                'accessible' => $route->isAccessible(),
                'external' => $route->is_external,
                'target' => $route->target,
                'sort_order' => $route->sort_order,
            ];

            // Add development flag for non-accessible routes
            if (!$route->isAccessible()) {
                $item['isDevelopment'] = $route->status === RouteStatus::DEVELOPMENT;
                $item['isDisabled'] = $route->status === RouteStatus::DISABLED;
                $item['isMaintenance'] = $route->status === RouteStatus::MAINTENANCE;
                $item['statusMessage'] = $route->getStatusMessage();
            }

            // Add children if they exist
            if ($route->children->isNotEmpty()) {
                $item['children'] = $this->formatNavigationItems($route->children);
            }

            $items[] = $item;
        }

        // Sort by sort_order
        usort($items, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $items;
    }

    /**
     * Get the URL for a route.
     */
    private function getRouteUrl(Route $route): string
    {
        if ($route->is_external) {
            return $route->path;
        }

        if ($route->redirect_url) {
            return $route->redirect_url;
        }

        // For non-accessible routes, return a placeholder
        if (!$route->isAccessible()) {
            return '#';
        }

        try {
            return route($route->name, $route->parameters ?? []);
        } catch (\Exception $e) {
            // If route doesn't exist, return the path
            return $route->path;
        }
    }

    /**
     * Check if the route is currently active.
     */
    private function isCurrentRoute(Route $route): bool
    {
        if (!function_exists('route')) {
            return false;
        }

        try {
            return request()->routeIs($route->name) || 
                   request()->routeIs($route->name . '.*');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get mobile bottom navigation items for a specific type.
     */
    public function getMobileBottomNavigation(RouteType $type): array
    {
        $routes = Route::active()
            ->navigation()
            ->byType($type)
            ->mobileVisible()
            ->parents()
            ->orderBy('sort_order')
            ->limit(4) // Limit to 4 items for bottom nav
            ->get();

        $items = $this->formatNavigationItems($routes);

        // Add menu item as the 5th item
        $items[] = [
            'name' => 'Menu',
            'href' => '#',
            'icon' => 'heroicon-o-bars-3',
            'current' => false,
            'action' => 'toggleMenu',
            'sort_order' => 999,
        ];

        return $items;
    }

    /**
     * Get breadcrumb items for a route.
     */
    public function getBreadcrumbs(string $routeName): array
    {
        $route = Route::where('name', $routeName)->first();
        
        if (!$route) {
            return [];
        }

        $breadcrumbs = [];
        $current = $route;

        // Build breadcrumb chain
        while ($current) {
            array_unshift($breadcrumbs, [
                'name' => $current->display_name,
                'href' => $current->isAccessible() ? $this->getRouteUrl($current) : '#',
                'current' => $current->id === $route->id,
            ]);

            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Check if a route is accessible and handle disabled routes.
     */
    public function checkRouteAccess(string $routeName): array
    {
        $route = Route::where('name', $routeName)->first();
        
        if (!$route) {
            return [
                'accessible' => true,
                'route' => null,
            ];
        }

        return [
            'accessible' => $route->isAccessible(),
            'route' => $route,
            'status' => $route->status,
            'message' => $route->getStatusMessage(),
            'title' => $route->status->getModalTitle(),
        ];
    }

    /**
     * Clear navigation cache.
     */
    public function clearCache(): void
    {
        Route::clearNavigationCache();
    }
}
