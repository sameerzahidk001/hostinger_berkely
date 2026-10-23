<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        TokenMismatchException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Never show the branded 419 page — return the user to the page they were on
     * (or home) with a refresh prompt.
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof TokenMismatchException) {
            return $this->renderSessionExpired($request);
        }

        $response = parent::render($request, $e);

        if ($response->getStatusCode() >= 400) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    protected function renderSessionExpired(Request $request): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Your session expired. Refresh the page and try again.',
                'session_expired' => true,
            ], 419);
        }

        $target = $this->sessionExpiredReturnUrl($request);

        return redirect()
            ->to($target)
            ->with('session_expired', true)
            ->withInput($request->except($this->dontFlash));
    }

    protected function sessionExpiredReturnUrl(Request $request): string
    {
        $home = url('/');
        $previous = (string) url()->previous();

        if ($previous !== '' && $this->isSameOriginUrl($previous) && $previous !== $request->fullUrl()) {
            return $previous;
        }

        if ($request->is('admin/login') || $request->routeIs('admin.auth', 'admin.login')) {
            return route('admin.login');
        }

        if ($request->is('login') || $request->is('*/login')) {
            return url('/login');
        }

        if (Auth::guard('admin')->check()) {
            return route('admin.home');
        }

        if (Auth::check()) {
            return route('user.home');
        }

        return $home;
    }

    protected function isSameOriginUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return str_starts_with($url, '/');
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST)
            ?: parse_url(url('/'), PHP_URL_HOST);

        return $appHost && strcasecmp((string) $host, (string) $appHost) === 0;
    }
}
