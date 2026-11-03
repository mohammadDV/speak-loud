<?php

namespace App\Support;

use Carbon\Carbon;

class ViewerTimezone
{
    public const COOKIE = 'viewer_timezone';

    public const FALLBACK = 'UTC';

    public static function current(): string
    {
        $candidate = request()->cookie(self::COOKIE)
            ?? request()->header('X-Viewer-Timezone');

        return self::normalize(is_string($candidate) ? $candidate : null);
    }

    public static function normalize(?string $timezone): string
    {
        if ($timezone === null || $timezone === '') {
            return self::FALLBACK;
        }

        $timezone = trim($timezone);

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            return self::FALLBACK;
        }

        return $timezone;
    }

    public static function label(?string $timezone = null): string
    {
        $timezone = self::normalize($timezone ?? self::current());
        $offset = Carbon::now($timezone)->format('P');

        return sprintf('%s (UTC%s)', $timezone, $offset);
    }

    public static function abbreviation(?string $timezone = null): string
    {
        $timezone = self::normalize($timezone ?? self::current());

        if ($timezone === 'UTC') {
            return 'UTC';
        }

        return Carbon::now($timezone)->format('T');
    }
}
