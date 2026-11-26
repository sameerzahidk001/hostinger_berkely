<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureJournalUser
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        if (! Auth::check()) {
            return redirect()->guest(path_login_url('academic-journal'));
        }

        if (! is_journal_user(Auth::user())) {
            abort(403, 'Journal access only.');
        }

        remember_path_auth_prefix('academic-journal');

        return $next($request);
    }
}
