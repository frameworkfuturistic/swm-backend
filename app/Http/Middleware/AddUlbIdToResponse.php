<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddUlbIdToResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Remove ulb_id if it is present in the request data (from client side)
        $request->offsetUnset('ulb_id');
        //check if logged in
        if (auth()->check()) {
            //since ulb_id is passed while login response in AuthController.login
            $request->merge(['ulb_id' => auth()->user()->ulb_id]);
        }

        return $next($request);
    }
}
