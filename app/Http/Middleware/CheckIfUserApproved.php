<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIfUserApproved
{
    public function handle($request, Closure $next)
    {
        // Exclude specific routes to prevent infinite loops
        if (in_array($request->route()->getName(), ['approval.notice', 'logout', 'verification.notice'])) {
            return $next($request);
        }

        // Check if user is logged in
        if (Auth::check()) {
            $user = auth()->user();

            // Ensure email is verified and user is not approved
            if ($user->email_verified_at !== null && $user->approved == 0) {
                return redirect()->route('approval.notice');
            }
        }

        return $next($request);
    }
}