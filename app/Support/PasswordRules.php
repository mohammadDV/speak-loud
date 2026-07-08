<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function rule(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * @return array<int, string|Password>
     */
    public static function validationRules(bool $confirmed = true): array
    {
        $rules = ['required', static::rule()];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    public static function requirements(): array
    {
        return [
            'At least 8 characters',
            'At least one uppercase letter (A–Z)',
            'At least one lowercase letter (a–z)',
            'At least one number (0–9)',
            'At least one symbol (!@#$%…)',
        ];
    }

    public static function instructions(): string
    {
        return implode(' · ', static::requirements());
    }
}
