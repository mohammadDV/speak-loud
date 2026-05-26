<?php

namespace App\Support;

class Legal
{
    public static function termsVersion(): string
    {
        return (string) config('legal.terms_version', '1');
    }
}
