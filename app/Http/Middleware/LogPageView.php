<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;

class LogPageView
{
    public function handle($request, Closure $next)
    {
        if (
            ! $request->isMethod('GET')
            || $request->ajax()
            || $request->is('api/*')
            || $request->is('admin/*')
            || $request->is('analytics/ping')
            || $request->is('user-behavior')
            || analytics_hidden_url($request->fullUrl())
        ) {
            return $next($request);
        }

        $response = $next($request);
        $status = $response->getStatusCode();
        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        if ($status >= 400 || ($contentType !== '' && ! str_contains($contentType, 'text/html'))) {
            return $response;
        }

        $userIp = visitor_client_ip($request) ?: $request->ip();
        $sessionId = $request->session()->getId();
        $fullUrl = $request->fullUrl();
        $userAgent = (string) $request->header('User-Agent');
        $referer = substr((string) $request->headers->get('referer'), 0, 500) ?: null;
        $today = now()->toDateString();
        $userId = Auth::id();

        app()->terminating(function () use ($userIp, $sessionId, $fullUrl, $userAgent, $referer, $today, $userId) {
            try {
                $agent = new Agent();
                $agent->setUserAgent($userAgent);

                PageView::ensureAnalyticsColumns();
                $pageView = DB::table('page_views')
                    ->where('session_id', $sessionId)
                    ->where('ip_address', $userIp)
                    ->where('url', $fullUrl)
                    ->whereDate('created_at', $today)
                    ->first();

                if ($pageView) {
                    $touch = ['updated_at' => now()];
                    if ($userId && Schema::hasColumn('page_views', 'user_id') && empty($pageView->user_id)) {
                        $touch['user_id'] = $userId;
                    }
                    DB::table('page_views')->where('id', $pageView->id)->increment('view_count', 1, $touch);

                    if (empty($pageView->country)) {
                        $location = resolve_ip_location($userIp);
                        if (! empty($location['country'])) {
                            DB::table('page_views')->where('id', $pageView->id)->update($location);
                        }
                    }

                    return;
                }

                $row = [
                    'url' => $fullUrl,
                    'session_id' => $sessionId,
                    'ip_address' => $userIp,
                    'country' => null,
                    'city' => null,
                    'region' => null,
                    'location' => null,
                    'postal' => null,
                    'user_agent' => $userAgent,
                    'browser' => $agent->browser(),
                    'platform' => $agent->platform(),
                    'view_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('page_views', 'user_id')) {
                    $row['user_id'] = $userId;
                }
                if (Schema::hasColumn('page_views', 'duration_seconds')) {
                    $row['duration_seconds'] = 0;
                }

                if (Schema::hasColumn('page_views', 'referrer')) {
                    $row['referrer'] = $referer;
                }

                $pageViewId = DB::table('page_views')->insertGetId($row);

                $location = resolve_ip_location($userIp);
                if (! empty($location['country'])) {
                    DB::table('page_views')->where('id', $pageViewId)->update($location);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return $response;
    }
}
