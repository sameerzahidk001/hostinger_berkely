<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToPreferredHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $preferred = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($preferred) || $preferred === '') {
            return $next($request);
        }

        $preferred = strtolower($preferred);
        $barePreferred = preg_replace('/^www\./i', '', $preferred) ?: $preferred;

        // Hostinger/CDN may rewrite Host; also check raw forwarded headers for www.
        $candidates = [];
        foreach ([
            $request->headers->get('X-Forwarded-Host'),
            $request->server->get('HTTP_X_FORWARDED_HOST'),
            $request->headers->get('X-Original-Host'),
            $request->server->get('HTTP_X_ORIGINAL_HOST'),
            $request->headers->get('CF-Visitor'),
            $request->server->get('HTTP_HOST'),
            $request->getHost(),
            $request->server->get('SERVER_NAME'),
        ] as $raw) {
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }
            // CF-Visitor is JSON; skip non-host values later via bare-host compare.
            $host = strtolower(trim(explode(',', $raw)[0]));
            $host = preg_replace('/:\d+$/', '', $host) ?: $host;
            if ($host !== '' && ! str_starts_with($host, '{')) {
                $candidates[$host] = true;
            }
        }

        // Also honor an explicit www request URI when proxies strip Host to apex.
        $requestUrl = strtolower((string) $request->fullUrl());
        if (str_contains($requestUrl, '://www.' . $barePreferred . '/') || str_contains($requestUrl, '://www.' . $barePreferred . '?')) {
            $candidates['www.' . $barePreferred] = true;
        }

        $seenWww = false;
        foreach (array_keys($candidates) as $host) {
            $bareHost = preg_replace('/^www\./i', '', $host) ?: $host;
            if (strcasecmp((string) $bareHost, (string) $barePreferred) !== 0) {
                continue;
            }
            if (str_starts_with($host, 'www.')) {
                $seenWww = true;
            }
        }

        if (! $seenWww || str_starts_with($preferred, 'www.')) {
            return $next($request);
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $target = $scheme . '://' . $preferred . $request->getRequestUri();

        return redirect()->away($target, 301);
    }
}
