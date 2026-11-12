<?php

namespace App\Domain\Dictionary\Support;

class TextNormalizer
{
    public static function normalize(string $text, ?string $languageCode = null): string
    {
        $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        $normalized = trim($normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        $code = $languageCode !== null ? strtolower(explode('-', $languageCode)[0]) : null;

        if ($code === 'tr') {
            $normalized = self::lowercaseTurkish($normalized);
        } else {
            $normalized = mb_strtolower($normalized);
        }

        return match ($code) {
            'de' => self::normalizeGerman($normalized),
            'fa', 'ar', 'ur' => self::normalizeArabicScript($normalized),
            'tr' => $normalized,
            default => $normalized,
        };
    }

    private static function lowercaseTurkish(string $text): string
    {
        // Apply Turkish-specific mappings before generic lowercasing.
        $text = str_replace(['İ', 'I'], ['i', 'ı'], $text);

        return mb_strtolower($text);
    }

    private static function normalizeGerman(string $text): string
    {
        return str_replace(
            ['ä', 'ö', 'ü', 'ß'],
            ['ae', 'oe', 'ue', 'ss'],
            $text,
        );
    }

    private static function normalizeArabicScript(string $text): string
    {
        $replacements = [
            "\u{064A}" => "\u{06CC}", // Arabic yeh → Persian yeh
            "\u{0643}" => "\u{06A9}", // Arabic kaf → Persian kaf
            "\u{0629}" => "\u{0647}", // teh marbuta → heh
            "\u{0623}" => "\u{0627}", // alef with hamza above → alef
            "\u{0625}" => "\u{0627}", // alef with hamza below → alef
            "\u{0622}" => "\u{0627}", // alef madda → alef
            "\u{0671}" => "\u{0627}", // alef wasla → alef
            "\u{200C}" => '', // ZWNJ
            "\u{200D}" => '', // ZWJ
        ];

        $text = strtr($text, $replacements);

        return preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text;
    }
}
