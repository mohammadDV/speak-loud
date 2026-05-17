<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name_en' => 'English',    'name_native' => 'English'],
            ['code' => 'es', 'name_en' => 'Spanish',    'name_native' => 'Español'],
            ['code' => 'fr', 'name_en' => 'French',     'name_native' => 'Français'],
            ['code' => 'de', 'name_en' => 'German',     'name_native' => 'Deutsch'],
            ['code' => 'it', 'name_en' => 'Italian',    'name_native' => 'Italiano'],
            ['code' => 'pt', 'name_en' => 'Portuguese', 'name_native' => 'Português'],
            ['code' => 'ru', 'name_en' => 'Russian',    'name_native' => 'Русский'],
            ['code' => 'ar', 'name_en' => 'Arabic',     'name_native' => 'العربية'],
            ['code' => 'zh', 'name_en' => 'Chinese',    'name_native' => '中文'],
            ['code' => 'ja', 'name_en' => 'Japanese',   'name_native' => '日本語'],
            ['code' => 'ko', 'name_en' => 'Korean',     'name_native' => '한국어'],
            ['code' => 'fa', 'name_en' => 'Persian',    'name_native' => 'فارسی'],
            ['code' => 'tr', 'name_en' => 'Turkish',    'name_native' => 'Türkçe'],
            ['code' => 'nl', 'name_en' => 'Dutch',      'name_native' => 'Nederlands'],
            ['code' => 'pl', 'name_en' => 'Polish',     'name_native' => 'Polski'],
            ['code' => 'sv', 'name_en' => 'Swedish',    'name_native' => 'Svenska'],
            ['code' => 'da', 'name_en' => 'Danish',     'name_native' => 'Dansk'],
            ['code' => 'no', 'name_en' => 'Norwegian',  'name_native' => 'Norsk'],
            ['code' => 'fi', 'name_en' => 'Finnish',    'name_native' => 'Suomi'],
            ['code' => 'hi', 'name_en' => 'Hindi',      'name_native' => 'हिन्दी'],
            ['code' => 'bn', 'name_en' => 'Bengali',    'name_native' => 'বাংলা'],
            ['code' => 'vi', 'name_en' => 'Vietnamese', 'name_native' => 'Tiếng Việt'],
            ['code' => 'th', 'name_en' => 'Thai',       'name_native' => 'ภาษาไทย'],
            ['code' => 'id', 'name_en' => 'Indonesian', 'name_native' => 'Bahasa Indonesia'],
            ['code' => 'ms', 'name_en' => 'Malay',      'name_native' => 'Bahasa Melayu'],
            ['code' => 'uk', 'name_en' => 'Ukrainian',  'name_native' => 'Українська'],
            ['code' => 'cs', 'name_en' => 'Czech',      'name_native' => 'Čeština'],
            ['code' => 'ro', 'name_en' => 'Romanian',   'name_native' => 'Română'],
            ['code' => 'hu', 'name_en' => 'Hungarian',  'name_native' => 'Magyar'],
            ['code' => 'el', 'name_en' => 'Greek',      'name_native' => 'Ελληνικά'],
            ['code' => 'he', 'name_en' => 'Hebrew',     'name_native' => 'עברית'],
            ['code' => 'ur', 'name_en' => 'Urdu',       'name_native' => 'اردو'],
            ['code' => 'sw', 'name_en' => 'Swahili',    'name_native' => 'Kiswahili'],
            ['code' => 'ca', 'name_en' => 'Catalan',    'name_native' => 'Català'],
            ['code' => 'hr', 'name_en' => 'Croatian',   'name_native' => 'Hrvatski'],
            ['code' => 'sk', 'name_en' => 'Slovak',     'name_native' => 'Slovenčina'],
            ['code' => 'bg', 'name_en' => 'Bulgarian',  'name_native' => 'Български'],
            ['code' => 'sr', 'name_en' => 'Serbian',    'name_native' => 'Српски'],
            ['code' => 'lt', 'name_en' => 'Lithuanian', 'name_native' => 'Lietuvių'],
            ['code' => 'lv', 'name_en' => 'Latvian',    'name_native' => 'Latviešu'],
        ];

        foreach ($languages as $lang) {
            Language::firstOrCreate(['code' => $lang['code']], $lang);
        }
    }
}
