<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the authenticated user has the "admin" role
        if ($request->user() && $request->user()->role === 'agency_admin') {
            return $next($request);
        }

        if ($request->user() && in_array($request->user()->role, ['agency_admin', 'municipal_office', 'accountant', 'team_leader']))
        {
            return $next($request);
        }

        // If not an admin, return a forbidden response
        return response()->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
    }
}
