<?php

namespace App\Domain\Learning\Enums;

enum MeaningQuizType: string
{
    case MultipleChoice = 'multiple_choice';
    case WriteIn = 'write_in';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Choose one of four',
            self::WriteIn => 'Type the meaning',
        };
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
