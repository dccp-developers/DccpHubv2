<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\RouteManagementService;

use Inertia\Inertia;

class CheckRouteAccess
{
    public function __construct(
        private RouteManagementService $routeService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        // Skip check if no route name or if it's an excluded route
        if (!$routeName || $this->isExcludedRoute($routeName)) {
            return $next($request);
        }

        // Check if route is accessible
        if (!$this->routeService->isRouteAccessible($routeName)) {
            $statusInfo = $this->routeService->getRouteStatusInfo($routeName);

            // For API requests, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Route not accessible',
                    'status' => $statusInfo['status']->value,
                    'title' => $statusInfo['title'],
                    'message' => $statusInfo['message'],
                ], 403);
            }

            // For Inertia requests, return modal response
            if ($request->header('X-Inertia')) {
                return Inertia::render('RouteDisabled', [
                    'status' => $statusInfo['status']->value,
                    'title' => $statusInfo['title'],
                    'message' => $statusInfo['message'],
                    'routeName' => $routeName,
                    'canGoBack' => $request->header('referer') !== null,
                ]);
            }

            // For regular requests, redirect with flash message
            return redirect()->back()->with('error', $statusInfo['message']);
        }

        return $next($request);
    }

    /**
     * Check if route should be excluded from access checks.
     */
    private function isExcludedRoute(string $routeName): bool
    {
        $excludedRoutes = [
            'login',
            'logout',
            'register',
            'password.*',
            'verification.*',
            'oauth.*',
            'filament.*',
            'livewire.*',
            'ignition.*',
            'debugbar.*',
            '_debugbar.*',
            'horizon.*',
            'telescope.*',
            'route-disabled',
        ];

        foreach ($excludedRoutes as $pattern) {
            if (str_contains($pattern, '*')) {
                $pattern = str_replace('*', '.*', $pattern);
                if (preg_match("/^{$pattern}$/", $routeName)) {
                    return true;
                }
            } elseif ($routeName === $pattern) {
                return true;
            }
        }

        return false;
    }
}
