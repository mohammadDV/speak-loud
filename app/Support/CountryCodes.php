<?php

namespace App\Support;

class CountryCodes
{
    /** @var array<string, string> ISO 3166-1 alpha-2 => English name */
    public const LIST = [
        'AF' => 'Afghanistan',
        'AR' => 'Argentina',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'BD' => 'Bangladesh',
        'BE' => 'Belgium',
        'BR' => 'Brazil',
        'CA' => 'Canada',
        'CL' => 'Chile',
        'CN' => 'China',
        'CO' => 'Colombia',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'EG' => 'Egypt',
        'FI' => 'Finland',
        'FR' => 'France',
        'DE' => 'Germany',
        'GR' => 'Greece',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KE' => 'Kenya',
        'KR' => 'South Korea',
        'KW' => 'Kuwait',
        'LB' => 'Lebanon',
        'MY' => 'Malaysia',
        'MX' => 'Mexico',
        'MA' => 'Morocco',
        'NL' => 'Netherlands',
        'NZ' => 'New Zealand',
        'NG' => 'Nigeria',
        'NO' => 'Norway',
        'PK' => 'Pakistan',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'QA' => 'Qatar',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'SA' => 'Saudi Arabia',
        'RS' => 'Serbia',
        'SG' => 'Singapore',
        'ZA' => 'South Africa',
        'ES' => 'Spain',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'TW' => 'Taiwan',
        'TH' => 'Thailand',
        'TR' => 'Turkey',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'VN' => 'Vietnam',
    ];

    public static function name(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $code = strtoupper($code);

        return self::LIST[$code] ?? $code;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $list = self::LIST;
        asort($list);

        return $list;
    }
}
