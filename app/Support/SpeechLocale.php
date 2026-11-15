<?php

namespace App\Support;

class SpeechLocale
{
    /**
     * Map app language codes to BCP-47 tags preferred by speechSynthesis voices.
     *
     * @var array<string, string>
     */
    private const LOCALES = [
        'en' => 'en-US',
        'es' => 'es-ES',
        'fr' => 'fr-FR',
        'de' => 'de-DE',
        'it' => 'it-IT',
        'pt' => 'pt-BR',
        'ru' => 'ru-RU',
        'ar' => 'ar-SA',
        'zh' => 'zh-CN',
        'ja' => 'ja-JP',
        'ko' => 'ko-KR',
        'fa' => 'fa-IR',
        'tr' => 'tr-TR',
        'nl' => 'nl-NL',
        'pl' => 'pl-PL',
        'sv' => 'sv-SE',
        'da' => 'da-DK',
        'no' => 'nb-NO',
        'fi' => 'fi-FI',
        'hi' => 'hi-IN',
        'bn' => 'bn-IN',
        'vi' => 'vi-VN',
        'th' => 'th-TH',
        'id' => 'id-ID',
        'ms' => 'ms-MY',
        'uk' => 'uk-UA',
        'cs' => 'cs-CZ',
        'ro' => 'ro-RO',
        'hu' => 'hu-HU',
        'el' => 'el-GR',
        'he' => 'he-IL',
        'ur' => 'ur-PK',
        'sw' => 'sw-KE',
        'ca' => 'ca-ES',
        'hr' => 'hr-HR',
        'sk' => 'sk-SK',
        'bg' => 'bg-BG',
        'sr' => 'sr-RS',
        'lt' => 'lt-LT',
        'lv' => 'lv-LV',
    ];

    public static function for(?string $languageCode): string
    {
        $code = strtolower(trim((string) $languageCode));

        if ($code === '') {
            return 'en-US';
        }

        if (isset(self::LOCALES[$code])) {
            return self::LOCALES[$code];
        }

        if (preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]+)?$/', $code) === 1) {
            return $code;
        }

        return 'en-US';
    }

    public static function german(): string
    {
        return self::for('de');
    }
}
