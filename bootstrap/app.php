<?php

use App\Http\Middleware\EnsureSessionVersionIsValid;
use App\Http\Middleware\EnsureUserIsActive;
use App\Support\TelegramExceptionFilter;
use App\Support\TelegramNotifier;
use App\Support\ViewerTimezone;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);

        $middleware->encryptCookies(except: [
            ViewerTimezone::COOKIE,
        ]);

        // Required for Auth::logoutOtherDevices() after password changes.
        // EnsureSessionVersionIsValid invalidates sessions across all session drivers.
        $middleware->web(append: [
            AuthenticateSession::class,
            EnsureSessionVersionIsValid::class,
        ]);

        // Proxy IPs come from config/trustedproxy.php (TRUSTED_PROXIES) so they
        // remain environment-specific after config:cache. Headers are set here.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            if (TelegramExceptionFilter::shouldSkip($e)) {
                return;
            }

            try {
                TelegramNotifier::error(TelegramNotifier::formatException($e));
            } catch (Throwable) {
                // Never let Telegram alerting break Laravel's exception reporter.
            }
        });
    })->create();
