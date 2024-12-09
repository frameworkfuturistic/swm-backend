<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfficeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the authenticated user has the "admin" role
        if ($request->user() && $request->user()->role === 'municipal_office') {
            return $next($request);
        }

        // If not an admin, return a forbidden response
        return response()->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
    }
}
