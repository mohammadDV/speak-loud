<?php

namespace App\Support;

use App\Models\Schedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ScheduleTime
{
    /**
     * @var array<string, string>
     */
    private const DAY_NAMES = [
        'Mon' => 'Monday',
        'Tue' => 'Tuesday',
        'Wed' => 'Wednesday',
        'Thu' => 'Thursday',
        'Fri' => 'Friday',
        'Sat' => 'Saturday',
        'Sun' => 'Sunday',
    ];

    public static function scheduleWhen(Schedule $schedule, ?string $viewerTimezone = null): ?string
    {
        $viewerTimezone = ViewerTimezone::normalize($viewerTimezone ?? ViewerTimezone::current());

        if ($schedule->recurringRule) {
            return self::formatRecurring($schedule, $viewerTimezone, long: false);
        }

        if ($schedule->oneTimeSlot) {
            return self::formatOneOff($schedule, $viewerTimezone, long: false);
        }

        return null;
    }

    public static function scheduleWhenLong(Schedule $schedule, ?string $viewerTimezone = null): ?string
    {
        $viewerTimezone = ViewerTimezone::normalize($viewerTimezone ?? ViewerTimezone::current());

        if ($schedule->recurringRule) {
            return self::formatRecurring($schedule, $viewerTimezone, long: true);
        }

        if ($schedule->oneTimeSlot) {
            return self::formatOneOff($schedule, $viewerTimezone, long: true);
        }

        return null;
    }

    public static function formatInstant(CarbonInterface $value, string $format, ?string $viewerTimezone = null): string
    {
        $viewerTimezone = ViewerTimezone::normalize($viewerTimezone ?? ViewerTimezone::current());

        return $value->copy()->timezone($viewerTimezone)->format($format);
    }

    public static function localLabel(?string $viewerTimezone = null): string
    {
        return ViewerTimezone::abbreviation($viewerTimezone);
    }

    private static function formatOneOff(Schedule $schedule, string $viewerTimezone, bool $long): string
    {
        $start = $schedule->oneTimeSlot->start_datetime->copy()->timezone($viewerTimezone);
        $end = $schedule->oneTimeSlot->end_datetime->copy()->timezone($viewerTimezone);
        $label = self::localLabel($viewerTimezone);

        if ($long) {
            return sprintf(
                '%s · %s – %s %s',
                $start->format('l, M j, Y'),
                $start->format('H:i'),
                $end->format('H:i'),
                $label,
            );
        }

        return sprintf(
            '%s · %s – %s %s',
            $start->format('D, M j'),
            $start->format('H:i'),
            $end->format('H:i'),
            $label,
        );
    }

    private static function formatRecurring(Schedule $schedule, string $viewerTimezone, bool $long): string
    {
        $rule = $schedule->recurringRule;
        $sourceTimezone = ViewerTimezone::normalize($rule->timezone ?: ViewerTimezone::FALLBACK);
        $startHm = substr((string) $rule->start_time, 0, 5);
        $endHm = substr((string) $rule->end_time, 0, 5);
        $sourceDays = array_values(array_filter(explode(',', (string) $rule->day_of_week)));

        /** @var array<string, array{start: string, end: string}> $byViewerDay */
        $byViewerDay = [];

        foreach ($sourceDays as $sourceDay) {
            $occurrence = self::nextOccurrence($sourceDay, $startHm, $sourceTimezone);
            $end = self::occurrenceEnd($occurrence, $endHm, $sourceTimezone);

            $localStart = $occurrence->copy()->timezone($viewerTimezone);
            $localEnd = $end->copy()->timezone($viewerTimezone);
            $viewerDay = $localStart->format('D');

            $byViewerDay[$viewerDay] = [
                'start' => $localStart->format('H:i'),
                'end' => $localEnd->format('H:i'),
            ];
        }

        $orderedDays = [];
        foreach (ScheduleDayOfWeek::CODES as $code) {
            if (isset($byViewerDay[$code])) {
                $orderedDays[] = $code;
            }
        }

        $dayLabel = implode(', ', $orderedDays);
        $times = array_values($byViewerDay);
        $start = $times[0]['start'] ?? $startHm;
        $end = $times[0]['end'] ?? $endHm;
        $sameRange = collect($times)->every(
            fn (array $range) => $range['start'] === $start && $range['end'] === $end
        );

        $label = self::localLabel($viewerTimezone);

        if (! $sameRange) {
            $parts = [];
            foreach ($orderedDays as $day) {
                $parts[] = sprintf(
                    '%s %s–%s',
                    $day,
                    $byViewerDay[$day]['start'],
                    $byViewerDay[$day]['end'],
                );
            }

            return implode(', ', $parts).' '.$label;
        }

        if ($long) {
            return "{$dayLabel} · {$start}–{$end} {$label}";
        }

        return "{$dayLabel} · {$start}–{$end} {$label}";
    }

    public static function nextOccurrence(string $dayCode, string $hhmm, string $timezone): Carbon
    {
        $timezone = ViewerTimezone::normalize($timezone);
        $dayName = self::DAY_NAMES[$dayCode] ?? 'Monday';
        [$hour, $minute] = UtcTime::split($hhmm);

        $cursor = Carbon::now($timezone)->startOfMinute();

        for ($i = 0; $i < 8; $i++) {
            $candidate = $cursor->copy()->addDays($i);
            if ($candidate->format('l') !== $dayName) {
                continue;
            }

            $candidate->setTime((int) $hour, (int) $minute, 0);

            if ($candidate->greaterThanOrEqualTo(Carbon::now($timezone)->subMinute())) {
                return $candidate;
            }
        }

        return Carbon::now($timezone)
            ->next($dayName)
            ->setTime((int) $hour, (int) $minute, 0);
    }

    public static function occurrenceEnd(Carbon $start, string $endHm, string $timezone): Carbon
    {
        $timezone = ViewerTimezone::normalize($timezone);
        [$hour, $minute] = UtcTime::split($endHm);

        $end = $start->copy()->timezone($timezone)->setTime((int) $hour, (int) $minute, 0);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $end;
    }

    /**
     * Convert a recurring wall-clock slot from one timezone to another for form editing.
     *
     * @param  list<string>  $sourceDays
     * @return array{days: list<string>, start: string, end: string}
     */
    public static function convertRecurringWallClock(
        array $sourceDays,
        string $startHm,
        string $endHm,
        string $fromTimezone,
        string $toTimezone,
    ): array {
        $fromTimezone = ViewerTimezone::normalize($fromTimezone);
        $toTimezone = ViewerTimezone::normalize($toTimezone);

        if ($fromTimezone === $toTimezone) {
            return [
                'days' => array_values(array_intersect(ScheduleDayOfWeek::CODES, $sourceDays)),
                'start' => UtcTime::join(...UtcTime::split($startHm)),
                'end' => UtcTime::join(...UtcTime::split($endHm)),
            ];
        }

        $viewerDays = [];
        $starts = [];
        $ends = [];

        foreach ($sourceDays as $sourceDay) {
            $code = ScheduleDayOfWeek::toCode($sourceDay);
            if ($code === null) {
                continue;
            }

            $start = self::nextOccurrence($code, $startHm, $fromTimezone);
            $end = self::occurrenceEnd($start, $endHm, $fromTimezone);
            $localStart = $start->copy()->timezone($toTimezone);
            $localEnd = $end->copy()->timezone($toTimezone);

            $viewerDays[] = $localStart->format('D');
            $starts[] = $localStart->format('H:i');
            $ends[] = $localEnd->format('H:i');
        }

        $viewerDays = array_values(array_intersect(ScheduleDayOfWeek::CODES, array_unique($viewerDays)));

        return [
            'days' => $viewerDays,
            'start' => $starts[0] ?? UtcTime::join(...UtcTime::split($startHm)),
            'end' => $ends[0] ?? UtcTime::join(...UtcTime::split($endHm)),
        ];
    }
}
