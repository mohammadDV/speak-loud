<?php

namespace App\Support;

class NormalizeEmail
{
    public static function normalize(?string $email): string
    {
        if ($email === null) {
            return '';
        }

        return strtolower(trim($email));
    }
}
