<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class LogPageView
{
    public function handle($request, Closure $next)
    {
        if ($request->is('api/*') || $request->is('admin/*')) {
            return $next($request);
        }

        try {
            $userIp = $request->ip();
            $agent = new Agent();
            $agent->setUserAgent($request->header('User-Agent'));

            $pageView = DB::table('page_views')
                ->where('session_id', $request->session()->getId())
                ->where('ip_address', $userIp)
                ->where('url', $request->fullUrl())
                ->whereDate('created_at', now()->toDateString())
                ->first();

            if ($pageView) {
                DB::table('page_views')->where('id', $pageView->id)->increment('view_count');
                DB::table('page_views')->where('id', $pageView->id)->update(['updated_at' => now()]);
            } else {
                $row = [
                    'url' => $request->fullUrl(),
                    'session_id' => $request->session()->getId(),
                    'ip_address' => $userIp,
                    'country' => null,
                    'city' => null,
                    'region' => null,
                    'location' => null,
                    'postal' => null,
                    'user_agent' => $request->header('User-Agent'),
                    'browser' => $agent->browser(),
                    'platform' => $agent->platform(),
                    'view_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('page_views', 'referrer')) {
                    $row['referrer'] = substr((string) $request->headers->get('referer'), 0, 500) ?: null;
                }

                DB::table('page_views')->insert($row);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }
}
