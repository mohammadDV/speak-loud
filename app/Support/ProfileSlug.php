<?php

namespace App\Support;

class ProfileSlug
{
    /** @var list<string> */
    public const RESERVED = [
        'about',
        'admin',
        'api',
        'blog',
        'claims',
        'contact',
        'discover',
        'login',
        'messages',
        'profile',
        'register',
        'schedule',
        'support',
        'u',
    ];

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::RESERVED, true);
    }

    public static function validationRules(?int $ignoreProfileId = null): array
    {
        $unique = 'unique:user_profiles,profile_slug';

        if ($ignoreProfileId !== null) {
            $unique .= ','.$ignoreProfileId;
        }

        return [
            'required',
            'string',
            'min:3',
            'max:50',
            'regex:/^[a-zA-Z0-9]+$/',
            $unique,
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) && self::isReserved($value)) {
                    $fail('This profile link is reserved. Please choose another.');
                }
            },
        ];
    }
}
