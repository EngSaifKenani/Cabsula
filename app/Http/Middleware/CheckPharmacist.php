<?php

namespace App\Http\Middleware;

use App\Models\Pharmacist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPharmacist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->user()->isPharmacist()) {
            abort(403, 'Permission denied');
        }

        return $next($request);
    }
}
