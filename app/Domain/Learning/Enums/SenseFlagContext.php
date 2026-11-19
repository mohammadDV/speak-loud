<?php

namespace App\Domain\Learning\Enums;

enum SenseFlagContext: string
{
    case Article = 'article';
    case Meaning = 'meaning';
    case Plural = 'plural';

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
