<?php

namespace App\Domain\Dictionary\Enums;

enum GrammaticalGender: string
{
    case Masculine = 'masculine';
    case Feminine = 'feminine';
    case Neuter = 'neuter';
}
