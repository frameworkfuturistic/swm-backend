<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TCMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
      // Check if the authenticated user has the "admin" role
      //   if ($request->user() && $request->user()->role === 'tax_collector') {
      //       return $next($request);
      //   }
      

      $user = $request->user();
      if ($user && in_array($user->role, ['tax_collector', 'team_leader']) && $user->is_active == 1) {
         return $next($request);
      }

       return format_response(
               'Access Denied',
               null,
               Response::HTTP_FORBIDDEN
         );

        // If not an admin, return a forbidden response
      //   return response()->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
    }
}
