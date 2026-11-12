<?php

namespace App\Domain\Dictionary\Enums;

enum TranslationType: string
{
    case Equivalent = 'equivalent';
    case Explanatory = 'explanatory';
    case Literal = 'literal';
    case Contextual = 'contextual';
}
