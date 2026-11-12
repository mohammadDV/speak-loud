<?php

namespace App\Domain\Dictionary\Enums;

enum GermanArticle: string
{
    case Der = 'der';
    case Die = 'die';
    case Das = 'das';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(?string $article): bool
    {
        if ($article === null || $article === '') {
            return false;
        }

        return in_array(mb_strtolower($article), self::values(), true);
    }
}
