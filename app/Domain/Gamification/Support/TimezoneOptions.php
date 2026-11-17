<?php

namespace App\Domain\Gamification\Support;

final class TimezoneOptions
{
    /**
     * Curated IANA zones for the settings selector (plus any current value).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function forSelect(?string $current = null): array
    {
        $zones = [
            'UTC',
            'Europe/Berlin',
            'Europe/London',
            'Europe/Paris',
            'Europe/Amsterdam',
            'Europe/Vienna',
            'Europe/Zurich',
            'Europe/Stockholm',
            'Europe/Madrid',
            'Europe/Rome',
            'Europe/Warsaw',
            'Europe/Athens',
            'Europe/Istanbul',
            'Asia/Tehran',
            'Asia/Dubai',
            'Asia/Kolkata',
            'Asia/Singapore',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'Australia/Sydney',
            'Africa/Cairo',
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'America/Sao_Paulo',
        ];

        if (is_string($current) && $current !== '' && ! in_array($current, $zones, true)
            && in_array($current, timezone_identifiers_list(), true)) {
            $zones[] = $current;
            sort($zones);
        }

        return array_map(static function (string $zone): array {
            $offset = now($zone)->format('P');

            return [
                'value' => $zone,
                'label' => str_replace('_', ' ', $zone).' (UTC'.$offset.')',
            ];
        }, $zones);
    }
}
