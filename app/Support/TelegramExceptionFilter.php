<?php

namespace App\Support;

use App\Jobs\SendTelegramMessage;
use App\Services\Telegram\TelegramClient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class TelegramExceptionFilter
{
    /**
     * @var list<class-string>
     */
    private const array TelegramInfrastructure = [
        SendTelegramMessage::class,
        TelegramClient::class,
        TelegramNotifier::class,
    ];

    public static function shouldSkip(Throwable $e): bool
    {
        if (self::isTelegramInfrastructureFailure($e)) {
            return true;
        }

        if ($e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException
            || $e instanceof NotFoundHttpException
            || $e instanceof ModelNotFoundException
            || $e instanceof TokenMismatchException
            || $e instanceof ThrottleRequestsException
            || $e instanceof TooManyRequestsHttpException
        ) {
            return true;
        }

        return $e instanceof HttpException && $e->getStatusCode() < 500;
    }

    /**
     * Avoid recursive job floods when Telegram delivery itself fails.
     */
    private static function isTelegramInfrastructureFailure(Throwable $e): bool
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            foreach ($current->getTrace() as $frame) {
                $class = $frame['class'] ?? null;

                if (is_string($class) && in_array($class, self::TelegramInfrastructure, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
