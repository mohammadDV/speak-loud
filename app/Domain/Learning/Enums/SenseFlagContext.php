<?php

namespace App\Domain\Learning\Enums;

enum SenseFlagContext: string
{
    case Article = 'article';
    case Meaning = 'meaning';

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
