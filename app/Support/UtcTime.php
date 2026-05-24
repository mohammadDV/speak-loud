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

    public static function scheduleWhen(Schedule $schedule): ?string
    {
        if ($schedule->recurringRule) {
            $days = str_replace(',', ', ', $schedule->recurringRule->day_of_week);
            $start = substr((string) $schedule->recurringRule->start_time, 0, 5);
            $end = substr((string) $schedule->recurringRule->end_time, 0, 5);

            return "{$days} · {$start}–{$end} ".static::LABEL;
        }

        if ($schedule->oneTimeSlot) {
            $start = static::format($schedule->oneTimeSlot->start_datetime, 'D, M j · H:i');
            $end = static::format($schedule->oneTimeSlot->end_datetime, 'H:i');

            return "{$start} – {$end} ".static::LABEL;
        }

        return null;
    }

    public static function scheduleWhenLong(Schedule $schedule): ?string
    {
        if ($schedule->recurringRule) {
            return static::scheduleWhen($schedule);
        }

        if ($schedule->oneTimeSlot) {
            $start = static::format($schedule->oneTimeSlot->start_datetime, 'l, M j, Y · H:i');
            $end = static::format($schedule->oneTimeSlot->end_datetime, 'H:i');

            return "{$start} – {$end} ".static::LABEL;
        }

        return null;
    }
}
