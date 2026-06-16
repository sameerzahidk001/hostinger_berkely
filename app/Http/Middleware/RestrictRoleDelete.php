<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictRoleDelete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['DELETE', 'delete'], true) && !admin_can_delete()) {
            abort(403, 'Delete action is not allowed for your role.');
        }

        if ($request->isMethod('post') && str_contains($request->path(), '/delete') && !admin_can_delete()) {
            abort(403, 'Delete action is not allowed for your role.');
        }

        return $next($request);
    }
}
