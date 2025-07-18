<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectMobileApp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect if this is a mobile app request
        $userAgent = $request->userAgent() ?? '';
        
        $isMobileApp = str_contains($userAgent, 'Capacitor') || 
                       str_contains($userAgent, 'DCCPHub-Mobile-App') ||
                       str_contains($userAgent, 'DCCPHub') ||
                       $request->header('X-Mobile-App') === 'true';
        
        // Add mobile app detection to request attributes
        $request->attributes->set('is_mobile_app', $isMobileApp);
        
        // Add header for easier detection in controllers
        if ($isMobileApp) {
            $request->headers->set('X-Mobile-App', 'true');
        }
        
        return $next($request);
    }
    
    /**
     * Check if the current request is from a mobile app
     */
    public static function isMobileApp(Request $request): bool
    {
        return $request->attributes->get('is_mobile_app', false) || 
               $request->header('X-Mobile-App') === 'true';
    }
}
