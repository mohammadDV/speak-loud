<?php

namespace App\Support;

use InvalidArgumentException;

class ScheduleDayOfWeek
{
    /** @var list<string> */
    public const CODES = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    /** @var array<string, string> */
    private const ALIASES = [
        'mon' => 'Mon', 'monday' => 'Mon',
        'tue' => 'Tue', 'tues' => 'Tue', 'tuesday' => 'Tue',
        'wed' => 'Wed', 'wednesday' => 'Wed',
        'thu' => 'Thu', 'thur' => 'Thu', 'thurs' => 'Thu', 'thursday' => 'Thu',
        'fri' => 'Fri', 'friday' => 'Fri',
        'sat' => 'Sat', 'saturday' => 'Sat',
        'sun' => 'Sun', 'sunday' => 'Sun',
    ];

    /**
     * @param  string|array<int, string>  $input
     */
    public static function normalize(string|array $input): string
    {
        if (is_array($input)) {
            $parts = $input;
        } else {
            $parts = preg_split('/\s*,\s*/', $input) ?: [];
        }

        $days = [];

        foreach ($parts as $part) {
            $code = self::toCode($part);

            if ($code !== null) {
                $days[$code] = $code;
            }
        }

        if ($days === []) {
            throw new InvalidArgumentException(
                'Use short day codes like Mon, Tue, Wed (not full names like Monday).'
            );
        }

        return implode(',', array_values(array_intersect(self::CODES, $days)));
    }

    public static function toCode(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (in_array($trimmed, self::CODES, true)) {
            return $trimmed;
        }

        $key = strtolower($trimmed);

        return self::ALIASES[$key] ?? null;
    }
}
