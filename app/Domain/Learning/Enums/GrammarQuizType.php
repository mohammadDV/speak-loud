<?php

namespace App\Domain\Learning\Enums;

enum GrammarQuizType: string
{
    case MultipleChoice = 'multiple_choice';
    case FillBlank = 'fill_blank';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Choose one of four',
            self::FillBlank => 'Fill in the blank',
        };
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
