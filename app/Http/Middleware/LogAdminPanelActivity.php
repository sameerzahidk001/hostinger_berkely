<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catch-all activity logging for Admin panel mutations and key opens.
 * Controllers that already call record_panel_activity() set panel_activity_recorded
 * so this middleware does not double-write.
 */
class LogAdminPanelActivity
{
    private const SKIP_ROUTE_NAMES = [
        'admin.csrf-token',
        'admin.session.ping',
        'admin.home.content-report-activity',
        'admin.logout',
        'admin.auth',
        'admin.login',
    ];

    private const SKIP_PATH_FRAGMENTS = [
        'csrf-token',
        'session/ping',
        'content-report-activity',
        'ckeditor-image-upload',
        'filemanager',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->attributes->get('panel_activity_recorded')) {
            return $response;
        }

        if (! $this->shouldLog($request, $response)) {
            return $response;
        }

        $action = $this->actionLabel($request);
        if ($action === '') {
            return $response;
        }

        $item = $this->itemLabel($request);
        $url = $request->fullUrl();

        try {
            // Mark before write so nested helpers do not loop if they re-enter.
            $request->attributes->set('panel_activity_recorded', true);
            record_panel_activity($action, $item, $url, $request);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! Auth::guard('admin')->check() && ! Auth::check()) {
            return false;
        }

        if ($request->ajax() && $request->wantsJson() && ! in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $status = $response->getStatusCode();
        if ($status >= 400) {
            return false;
        }

        if ($request->hasSession() && $request->session()->has('errors')) {
            return false;
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName !== '' && in_array($routeName, self::SKIP_ROUTE_NAMES, true)) {
            return false;
        }

        $path = strtolower($request->path());
        foreach (self::SKIP_PATH_FRAGMENTS as $fragment) {
            if (str_contains($path, $fragment)) {
                return false;
            }
        }

        $method = strtoupper($request->method());

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        if ($method === 'GET') {
            return $this->isSignificantOpen($request, $routeName);
        }

        return false;
    }

    private function isSignificantOpen(Request $request, string $routeName): bool
    {
        if (preg_match('/\.(create|edit|show)$/i', $routeName)) {
            return true;
        }

        $path = $request->path();

        return (bool) preg_match('#/(create|edit|fees)(/|$|\?)#i', $path)
            || (bool) preg_match('#/(pages|course|courses|payments|seo|training-calendar|course-agendas|study-materials|class-schedules)/[^/]+$#i', $path);
    }

    private function actionLabel(Request $request): string
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $method = strtoupper($request->method());
        $parts = $this->routeParts($routeName);
        $last = strtolower((string) (end($parts) ?: ''));

        $verbMap = [
            'store' => 'Created',
            'update' => 'Updated',
            'destroy' => 'Deleted',
            'delete' => 'Deleted',
            'duplicate' => 'Duplicated',
            'create' => 'Opened Create',
            'edit' => 'Opened Edit',
            'show' => 'Opened',
            'index' => 'Opened List',
            'export' => 'Exported',
            'send' => 'Sent',
            'approve' => 'Approved',
            'reject' => 'Rejected',
            'restore' => 'Restored',
            'toggle' => 'Toggled',
            'copy' => 'Copied',
            'sync' => 'Synced',
        ];

        $verb = $verbMap[$last] ?? null;
        if ($verb !== null) {
            array_pop($parts);
        }

        $subject = $this->titleCaseParts($parts);

        if ($method === 'GET') {
            if ($verb !== null && str_starts_with($verb, 'Opened')) {
                return trim($verb . ($subject !== '' ? ' ' . $subject : ''));
            }

            return 'Opened' . ($subject !== '' ? ' ' . $subject : '');
        }

        if ($verb !== null) {
            return trim(($subject !== '' ? $subject . ' ' : '') . $verb);
        }

        return trim(($subject !== '' ? $subject . ' ' : 'Admin ') . ucfirst(strtolower($method)));
    }

    private function itemLabel(Request $request): string
    {
        foreach ([
            'page_name', 'title', 'name', 'package_name', 'subject', 'email',
            'username', 'label', 'heading', 'course_title',
        ] as $key) {
            $value = trim((string) $request->input($key, ''));
            if ($value !== '') {
                return \Illuminate\Support\Str::limit($value, 180, '…');
            }
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName !== '') {
            return $this->titleCaseParts($this->routeParts($routeName));
        }

        return \Illuminate\Support\Str::limit($request->path(), 180, '…');
    }

    private function routeParts(string $routeName): array
    {
        $routeName = preg_replace('/^admin\./', '', $routeName) ?? $routeName;
        $parts = preg_split('/[._-]+/', $routeName) ?: [];

        return array_values(array_filter($parts, fn ($p) => $p !== '' && strtolower((string) $p) !== 'admin'));
    }

    private function titleCaseParts(array $parts): string
    {
        $parts = array_map(function ($part) {
            $part = str_replace(['-', '_'], ' ', (string) $part);

            return ucwords($part);
        }, $parts);

        return trim(implode(' ', $parts));
    }
}
