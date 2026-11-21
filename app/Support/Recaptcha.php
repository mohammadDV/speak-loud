<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

class Recaptcha
{
    public const string VerifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

    public const string FailureMessage = 'Could not verify you are human. Please try again.';

    /** @var list<string> */
    public const array AllowedActions = ['register', 'login', 'forgot_password'];

    public static function enabled(): bool
    {
        return (bool) config('services.recaptcha.enabled', false)
            && filled(config('services.recaptcha.secret_key'))
            && filled(config('services.recaptcha.site_key'));
    }

    public static function siteKey(): ?string
    {
        $key = config('services.recaptcha.site_key');

        return filled($key) ? (string) $key : null;
    }

    public static function scoreThreshold(): float
    {
        $threshold = (float) config('services.recaptcha.score_threshold', 0.5);

        if ($threshold < 0.0) {
            return 0.0;
        }

        if ($threshold > 1.0) {
            return 1.0;
        }

        return $threshold;
    }

    /**
     * Verify a reCAPTCHA v3 token for the expected action.
     * When disabled (local/CI without keys), verification is skipped.
     */
    public static function verify(?string $token, string $action): bool
    {
        if (! self::enabled()) {
            return true;
        }

        if (! in_array($action, self::AllowedActions, true)) {
            return false;
        }

        if (! is_string($token) || trim($token) === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post(self::VerifyUrl, [
                    'secret' => (string) config('services.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        /** @var array{success?: mixed, score?: mixed, action?: mixed, hostname?: mixed} $payload */
        $payload = $response->json() ?? [];

        if (($payload['success'] ?? false) !== true) {
            return false;
        }

        if (($payload['action'] ?? null) !== $action) {
            return false;
        }

        if (! self::hostnameIsAllowed($payload['hostname'] ?? null)) {
            return false;
        }

        $score = (float) ($payload['score'] ?? 0);

        return $score >= self::scoreThreshold();
    }

    private static function hostnameIsAllowed(mixed $hostname): bool
    {
        if (! is_string($hostname) || $hostname === '') {
            return false;
        }

        $allowed = array_values(array_unique(array_filter([
            request()->getHost(),
            parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ])));

        return in_array($hostname, $allowed, true);
    }
}
