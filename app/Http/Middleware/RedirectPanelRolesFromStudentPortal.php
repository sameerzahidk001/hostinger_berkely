<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectPanelRolesFromStudentPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::guard('admin')->check()) {
            $role = Auth::user()->roles()->value('name');

            if (is_restricted_panel_role($role)) {
                return redirect()->route('admin.home');
            }
        }

        return $next($request);
    }
}
