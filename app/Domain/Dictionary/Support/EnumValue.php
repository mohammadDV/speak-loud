<?php

namespace App\Domain\Dictionary\Support;

use BackedEnum;

final class EnumValue
{
    public static function string(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        return null;
    }
}
