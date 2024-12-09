<?php

namespace App\Http\Middleware;

use App\Models\Param;
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
            $ulb_id = auth()->user()->ulb_id;
            //since ulb_id is passed while login response in AuthController.login
            $request->merge(['ulb_id' => $ulb_id]);

            // Fetch all parameters for the `ulb_id`
            $params = Param::where('ulb_id', $ulb_id)->get();

            foreach ($params as $param) {
                $value = null;
                switch ($param->param_type) {
                    case 'STRING':
                        $value = $param->param_string;
                        break;

                    case 'INT':
                        $value = $param->param_int;
                        break;

                    case 'BOOL':
                        $value = $param->param_bool;
                        break;

                    case 'DATE':
                        $value = $param->param_date;
                        break;
                }

                if ($value !== null) { // Only include non-null values
                    $request->merge([$param->param_name => $value]);
                }
            }
        }

        return $next($request);
    }
}
