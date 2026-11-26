<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        $path = trim($request->path(), '/');
        if (str_starts_with($path, 'academic-journal')) {
            return url('/academic-journal/login');
        }
        if (str_starts_with($path, 'events')) {
            return url('/events/login');
        }

        return route('login');
    }
}
