<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;

class LogPageView
{
    public function handle($request, Closure $next)
    {
        if ($request->is('api/*') || $request->is('admin/*')) {
            return $next($request);
        }

        $response = $next($request);

        $userIp = $request->ip();
        $sessionId = $request->session()->getId();
        $fullUrl = $request->fullUrl();
        $userAgent = (string) $request->header('User-Agent');
        $referer = substr((string) $request->headers->get('referer'), 0, 500) ?: null;
        $today = now()->toDateString();

        app()->terminating(function () use ($userIp, $sessionId, $fullUrl, $userAgent, $referer, $today) {
            try {
                $agent = new Agent();
                $agent->setUserAgent($userAgent);

                $pageView = DB::table('page_views')
                    ->where('session_id', $sessionId)
                    ->where('ip_address', $userIp)
                    ->where('url', $fullUrl)
                    ->whereDate('created_at', $today)
                    ->first();

                if ($pageView) {
                    DB::table('page_views')->where('id', $pageView->id)->increment('view_count');

                    if (empty($pageView->country)) {
                        $location = resolve_ip_location($userIp);
                        if (! empty($location['country'])) {
                            DB::table('page_views')->where('id', $pageView->id)->update(array_merge($location, [
                                'updated_at' => now(),
                            ]));
                        } else {
                            DB::table('page_views')->where('id', $pageView->id)->update(['updated_at' => now()]);
                        }
                    } else {
                        DB::table('page_views')->where('id', $pageView->id)->update(['updated_at' => now()]);
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
