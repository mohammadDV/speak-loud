<?php

namespace App\Domain\Gamification\Support;

use Carbon\CarbonImmutable;

final class CompetitionPeriod
{
    public function __construct(
        public readonly CarbonImmutable $startUtc,
        public readonly CarbonImmutable $endUtc,
        public readonly CarbonImmutable $startLocal,
        public readonly string $timezone,
    ) {}

    public static function forDate(?CarbonImmutable $reference = null, ?string $timezone = null): self
    {
        $timezone ??= (string) config('gamification.competition_timezone', 'Europe/Berlin');
        $local = ($reference ?? CarbonImmutable::now($timezone))->timezone($timezone);
        $startLocal = $local->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
        $endLocal = $startLocal->addWeek();

        return new self(
            startUtc: $startLocal->utc(),
            endUtc: $endLocal->utc(),
            startLocal: $startLocal,
            timezone: $timezone,
        );
    }

    public static function forWeekStart(CarbonImmutable $weekStartLocal): self
    {
        $timezone = (string) config('gamification.competition_timezone', 'Europe/Berlin');
        $startLocal = $weekStartLocal->timezone($timezone)->startOfDay();

        return new self(
            startUtc: $startLocal->utc(),
            endUtc: $startLocal->addWeek()->utc(),
            startLocal: $startLocal,
            timezone: $timezone,
        );
    }

    /**
     * Plain array safe for Livewire / JSON (no Carbon objects).
     *
     * @return array{start_utc: string, end_utc: string, start_local: string, timezone: string}
     */
    public function toArray(): array
    {
        return [
            'start_utc' => $this->startUtc->toIso8601String(),
            'end_utc' => $this->endUtc->toIso8601String(),
            'start_local' => $this->startLocal->toIso8601String(),
            'timezone' => $this->timezone,
        ];
    }
}
