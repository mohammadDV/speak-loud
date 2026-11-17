<?php

namespace App\Domain\Gamification\Support;

final class ActivityRegistry
{
    /**
     * @return array<string, array{
     *     label: string,
     *     url: string,
     *     flag_context: string,
     *     seconds_per_question: int,
     *     qualifies_for_streak: bool,
     *     xp: array<string, int>,
     *     modes: array<string, array{xp?: array<string, int>}>
     * }>
     */
    public function all(): array
    {
        /** @var array<string, array<string, mixed>> $activities */
        $activities = config('gamification.activities', []);

        $normalized = [];
        foreach ($activities as $type => $activity) {
            $normalized[$type] = [
                'label' => (string) ($activity['label'] ?? $type),
                'url' => (string) ($activity['url'] ?? '/learn'),
                'flag_context' => (string) ($activity['flag_context'] ?? ''),
                'seconds_per_question' => (int) ($activity['seconds_per_question'] ?? 18),
                'qualifies_for_streak' => (bool) ($activity['qualifies_for_streak'] ?? true),
                'xp' => is_array($activity['xp'] ?? null) ? $activity['xp'] : [],
                'modes' => is_array($activity['modes'] ?? null) ? $activity['modes'] : [],
            ];
        }

        return $normalized;
    }

    public function has(string $activityType): bool
    {
        return array_key_exists($activityType, $this->all());
    }

    /**
     * @return array{
     *     label: string,
     *     url: string,
     *     flag_context: string,
     *     seconds_per_question: int,
     *     qualifies_for_streak: bool,
     *     xp: array<string, int>,
     *     modes: array<string, array{xp?: array<string, int>}>
     * }|null
     */
    public function get(string $activityType): ?array
    {
        return $this->all()[$activityType] ?? null;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }
}
