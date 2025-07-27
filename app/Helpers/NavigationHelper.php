<?php

namespace App\Helpers;

use App\Services\NavigationService;
use App\Enums\RouteType;
use Illuminate\Support\Facades\Auth;

class NavigationHelper
{
    private NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Get navigation items based on user role.
     */
    public function getNavigationForUser(?string $userRole = null, bool $mobileOnly = false): array
    {
        $role = $userRole ?: Auth::user()?->role;

        return match ($role) {
            'student' => $this->navigationService->getStudentNavigation($mobileOnly),
            'faculty' => $this->navigationService->getFacultyNavigation($mobileOnly),
            'admin' => $this->navigationService->getAdminNavigation($mobileOnly),
            default => $this->navigationService->getPublicNavigation($mobileOnly),
        };
    }

    /**
     * Get mobile bottom navigation for user.
     */
    public function getMobileBottomNavigationForUser(?string $userRole = null): array
    {
        $role = $userRole ?: Auth::user()?->role;

        $type = match ($role) {
            'student' => RouteType::STUDENT,
            'faculty' => RouteType::FACULTY,
            'admin' => RouteType::ADMIN,
            default => RouteType::PUBLIC,
        };

        return $this->navigationService->getMobileBottomNavigation($type);
    }

    /**
     * Get navigation config for Vue components.
     */
    public function getNavigationConfig(?string $userRole = null): array
    {
        $role = $userRole ?: Auth::user()?->role;
        $navigation = $this->getNavigationForUser($role);

        // Group navigation items
        $grouped = [
            [
                'label' => 'Platform',
                'items' => array_filter($navigation, fn($item) => !in_array($item['name'], ['Support', 'Change-Log'])),
            ],
            [
                'label' => null,
                'class' => 'mt-auto',
                'items' => array_filter($navigation, fn($item) => in_array($item['name'], ['Support', 'Change-Log'])),
            ],
        ];

        return array_filter($grouped, fn($group) => !empty($group['items']));
    }

    /**
     * Get mobile navigation config for Vue components.
     */
    public function getMobileNavigationConfig(?string $userRole = null): array
    {
        $role = $userRole ?: Auth::user()?->role;
        $navigation = $this->getNavigationForUser($role, true);

        // Separate quick actions from main menu
        $quickActions = array_slice($navigation, 0, 4);
        $mainMenu = array_slice($navigation, 4);

        return [
            'quickActions' => $quickActions,
            'mainMenu' => $mainMenu,
        ];
    }

    /**
     * Convert route item to Vue-compatible format.
     */
    public function formatForVue(array $item): array
    {
        return [
            'name' => $item['name'],
            'icon' => $this->convertIconFormat($item['icon']),
            'route' => $item['route'] ?? null,
            'href' => $item['href'] ?? null,
            'external' => $item['external'] ?? false,
            'current' => $item['current'] ?? false,
            'accessible' => $item['accessible'] ?? true,
            'isDevelopment' => $item['isDevelopment'] ?? false,
            'isDisabled' => $item['isDisabled'] ?? false,
            'isMaintenance' => $item['isMaintenance'] ?? false,
            'statusMessage' => $item['statusMessage'] ?? null,
            'target' => $item['target'] ?? '_self',
            'action' => $item['action'] ?? null,
        ];
    }

    /**
     * Convert Heroicon format to Lucide format for Vue components.
     */
    private function convertIconFormat(string $icon): string
    {
        // Map common Heroicons to Lucide equivalents
        $iconMap = [
            'heroicon-o-squares-2x2' => 'lucide:layout-dashboard',
            'heroicon-o-home' => 'lucide:home',
            'heroicon-o-calendar' => 'lucide:calendar',
            'heroicon-o-banknote' => 'lucide:banknote',
            'heroicon-o-book-open' => 'lucide:book',
            'heroicon-o-academic-cap' => 'lucide:graduation-cap',
            'heroicon-o-users' => 'lucide:users',
            'heroicon-o-clipboard-document-list' => 'lucide:clipboard-list',
            'heroicon-o-chart-bar' => 'lucide:bar-chart',
            'heroicon-o-document-text' => 'lucide:file-text',
            'heroicon-o-cog' => 'lucide:settings',
            'heroicon-o-settings' => 'lucide:settings',
            'heroicon-o-life-buoy' => 'lucide:life-buoy',
            'heroicon-o-bars-3' => 'lucide:menu',
            'heroicon-o-x-circle' => 'lucide:x-circle',
            'heroicon-o-check-circle' => 'lucide:check-circle',
            'heroicon-o-wrench-screwdriver' => 'lucide:wrench',
            'heroicon-o-code-bracket' => 'lucide:code',
            'heroicon-o-map' => 'lucide:map',
        ];

        return $iconMap[$icon] ?? $icon;
    }

    /**
     * Get breadcrumbs for current route.
     */
    public function getCurrentBreadcrumbs(): array
    {
        $routeName = request()->route()?->getName();
        
        if (!$routeName) {
            return [];
        }

        return $this->navigationService->getBreadcrumbs($routeName);
    }

    /**
     * Check if current route is accessible.
     */
    public function checkCurrentRouteAccess(): array
    {
        $routeName = request()->route()?->getName();
        
        if (!$routeName) {
            return ['accessible' => true];
        }

        return $this->navigationService->checkRouteAccess($routeName);
    }

    /**
     * Get route status for JavaScript.
     */
    public function getRouteStatusForJs(string $routeName): array
    {
        return $this->navigationService->checkRouteAccess($routeName);
    }
}
