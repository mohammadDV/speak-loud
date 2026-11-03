<?php

namespace App\Support;

use App\Models\Schedule;
use Carbon\CarbonInterface;

class UtcTime
{
    public const LABEL = 'UTC';

    public static function format(CarbonInterface $value, string $format): string
    {
        return $value->copy()->utc()->format($format);
    }

    public static function formatForInput(CarbonInterface $value): string
    {
        return static::format($value, 'Y-m-d\TH:i');
    }

    /**
     * @return array<string, string>
     */
    public static function hours(): array
    {
        $hours = [];

        for ($hour = 0; $hour <= 23; $hour++) {
            $value = sprintf('%02d', $hour);
            $hours[$value] = $value;
        }

        return $hours;
    }

    /**
     * @return array<string, string>
     */
    public static function minutes(): array
    {
        $minutes = [];

        for ($minute = 0; $minute <= 59; $minute++) {
            $value = sprintf('%02d', $minute);
            $minutes[$value] = $value;
        }

        return $minutes;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function split(string $hhmm): array
    {
        if (preg_match('/^(\d{1,2}):(\d{2})/', $hhmm, $matches) !== 1) {
            return ['00', '00'];
        }

        return [
            sprintf('%02d', min(23, max(0, (int) $matches[1]))),
            sprintf('%02d', min(59, max(0, (int) $matches[2]))),
        ];
    }

    public static function join(string $hour, string $minute): string
    {
        return sprintf(
            '%02d:%02d',
            min(23, max(0, (int) $hour)),
            min(59, max(0, (int) $minute)),
        );
    }

    public static function dateFromDatetimeLocal(string $datetimeLocal): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $datetimeLocal, $matches) !== 1) {
            return '';
        }

        return $matches[1];
    }

    public static function timeFromDatetimeLocal(string $datetimeLocal): string
    {
        if (preg_match('/T(\d{2}:\d{2})/', $datetimeLocal, $matches) !== 1) {
            return '00:00';
        }

        return $matches[1];
    }

    public static function composeDatetimeLocal(string $date, string $hhmm): string
    {
        if ($date === '') {
            return '';
        }

        return $date.'T'.static::join(...static::split($hhmm));
    }

    public static function scheduleWhen(Schedule $schedule): ?string
    {
        return ScheduleTime::scheduleWhen($schedule);
    }

    public static function scheduleWhenLong(Schedule $schedule): ?string
    {
        return ScheduleTime::scheduleWhenLong($schedule);
    }
}
