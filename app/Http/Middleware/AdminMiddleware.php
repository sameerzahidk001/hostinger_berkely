<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user()?->fresh();

            if ($admin) {
                Auth::guard('admin')->setUser($admin);
            }

            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            $role = $user?->roles()->value('name');

            if (is_restricted_panel_role($role)) {
                $freshUser = $user?->fresh(['roles']);

                if ($freshUser) {
                    Auth::setUser($freshUser);
                }

                return $next($request);
            }
        }

        return redirect()->to(public_login_url());
    }
}
