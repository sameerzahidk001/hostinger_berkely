<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();

        if (!method_exists($user, 'hasPermission') || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized: You do not have permission to access this page.');
        }

        return $next($request);
    }
}