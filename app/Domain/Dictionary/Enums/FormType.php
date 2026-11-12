<?php

namespace App\Domain\Dictionary\Enums;

enum FormType: string
{
    case Inflected = 'inflected';
    case AlternativeSpelling = 'alternative_spelling';
    case Transliteration = 'transliteration';
    case Variant = 'variant';
}
