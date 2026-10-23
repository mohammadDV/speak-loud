<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class SafeInternalRedirect
{
    /**
     * Return $url only when it points at this application; otherwise $fallback.
     */
    public static function url(?string $url, string $fallback): string
    {
        if ($url === null || $url === '') {
            return $fallback;
        }

        $root = rtrim(URL::to('/'), '/');

        if (str_starts_with($url, $root.'/') || $url === $root) {
            return $url;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return URL::to($url);
        }

        return $fallback;
    }
}
