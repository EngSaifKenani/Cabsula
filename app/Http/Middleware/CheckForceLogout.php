<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckForceLogout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->last_force_logout) {
            if (auth()->user()->last_force_logout > auth()->user()->currentAccessToken()->created_at) {
                auth()->user()->currentAccessToken()->delete();
                return response()->json(['message' => 'Session terminated. Please login again.'], 401);
            }
        }

        return $next($request);
    }
}
