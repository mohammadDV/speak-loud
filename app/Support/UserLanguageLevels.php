<?php

namespace App\Support;

class UserLanguageLevels
{
    public const LEVELS = [
        'beginner',
        'elementary',
        'intermediate',
        'upper_intermediate',
        'advanced',
        'fluent',
    ];

    public const TYPES = [
        'native',
        'learning',
    ];

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'beginner' => 'A1 – Beginner',
            'elementary' => 'A2 – Elementary',
            'intermediate' => 'B1 – Intermediate',
            'upper_intermediate' => 'B2 – Upper Intermediate',
            'advanced' => 'C1 – Advanced',
            'fluent' => 'C2 – Fluent',
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'native' => 'Native',
            'learning' => 'Learning',
            default => ucfirst($type),
        };
    }
}
