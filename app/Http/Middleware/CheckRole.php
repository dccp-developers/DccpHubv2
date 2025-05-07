<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if the user is authenticated
        if (!$request->user()) {
            return redirect()->route('login');
        }
        
        // Check if the user has the required role
        if ($request->user()->role !== $role) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access this page.');
        }
        
        return $next($request);
    }
} 