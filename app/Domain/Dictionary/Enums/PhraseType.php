<?php

namespace App\Domain\Dictionary\Enums;

enum PhraseType: string
{
    case Collocation = 'collocation';
    case FixedExpression = 'fixed_expression';
    case Idiom = 'idiom';
}
