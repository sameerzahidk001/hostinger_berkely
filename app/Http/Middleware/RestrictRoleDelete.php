<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictRoleDelete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (str_starts_with(trim($request->path(), '/'), 'admin/assessments')) {
            return $next($request);
        }

        if (in_array($request->method(), ['DELETE', 'delete'], true) && !admin_can_delete()) {
            if ($this->isLmsSittingDelete($request)) {
                return $next($request);
            }
            abort(403, 'Delete action is not allowed for your role.');
        }

        if ($request->isMethod('post') && str_contains($request->path(), '/delete') && !admin_can_delete()) {
            if ($this->isLmsSittingDelete($request)) {
                return $next($request);
            }
            abort(403, 'Delete action is not allowed for your role.');
        }

        return $next($request);
    }

    /** Assigned-test sitting/attempt delete is authorised in the LMS controller, not this role lock. */
    protected function isLmsSittingDelete(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return (bool) preg_match(
            '#^admin/assessments/(assignments|attempts|reports/attempts)/\d+$#',
            $path
        );
    }
}
