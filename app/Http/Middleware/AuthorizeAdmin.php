<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Constants\Roles;

class AuthorizeAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(!$request->user()->hasRole(Roles::ADMIN) && !$request->user()->hasRole(Roles::SUPER_ADMIN)){
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'Only administrators are authorized to query this endpoint',
            ], 403);
        }
        return $next($request);
    }
}
