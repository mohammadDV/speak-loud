<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $rtlArabic = ['script' => 'Arabic', 'direction' => 'rtl'];
        $ltrLatin = ['script' => 'Latin', 'direction' => 'ltr'];
        $ltrCyrillic = ['script' => 'Cyrillic', 'direction' => 'ltr'];
        $ltrHan = ['script' => 'Han', 'direction' => 'ltr'];

        $languages = [
            ['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', ...$ltrLatin],
            ['code' => 'es', 'name_en' => 'Spanish', 'name_native' => 'Español', ...$ltrLatin],
            ['code' => 'fr', 'name_en' => 'French', 'name_native' => 'Français', ...$ltrLatin],
            ['code' => 'de', 'name_en' => 'German', 'name_native' => 'Deutsch', ...$ltrLatin],
            ['code' => 'it', 'name_en' => 'Italian', 'name_native' => 'Italiano', ...$ltrLatin],
            ['code' => 'pt', 'name_en' => 'Portuguese', 'name_native' => 'Português', ...$ltrLatin],
            ['code' => 'ru', 'name_en' => 'Russian', 'name_native' => 'Русский', ...$ltrCyrillic],
            ['code' => 'ar', 'name_en' => 'Arabic', 'name_native' => 'العربية', ...$rtlArabic],
            ['code' => 'zh', 'name_en' => 'Chinese', 'name_native' => '中文', ...$ltrHan],
            ['code' => 'ja', 'name_en' => 'Japanese', 'name_native' => '日本語', 'script' => 'Japanese', 'direction' => 'ltr'],
            ['code' => 'ko', 'name_en' => 'Korean', 'name_native' => '한국어', 'script' => 'Hangul', 'direction' => 'ltr'],
            ['code' => 'fa', 'name_en' => 'Persian', 'name_native' => 'فارسی', ...$rtlArabic],
            ['code' => 'tr', 'name_en' => 'Turkish', 'name_native' => 'Türkçe', ...$ltrLatin],
            ['code' => 'nl', 'name_en' => 'Dutch', 'name_native' => 'Nederlands', ...$ltrLatin],
            ['code' => 'pl', 'name_en' => 'Polish', 'name_native' => 'Polski', ...$ltrLatin],
            ['code' => 'sv', 'name_en' => 'Swedish', 'name_native' => 'Svenska', ...$ltrLatin],
            ['code' => 'da', 'name_en' => 'Danish', 'name_native' => 'Dansk', ...$ltrLatin],
            ['code' => 'no', 'name_en' => 'Norwegian', 'name_native' => 'Norsk', ...$ltrLatin],
            ['code' => 'fi', 'name_en' => 'Finnish', 'name_native' => 'Suomi', ...$ltrLatin],
            ['code' => 'hi', 'name_en' => 'Hindi', 'name_native' => 'हिन्दी', 'script' => 'Devanagari', 'direction' => 'ltr'],
            ['code' => 'bn', 'name_en' => 'Bengali', 'name_native' => 'বাংলা', 'script' => 'Bengali', 'direction' => 'ltr'],
            ['code' => 'vi', 'name_en' => 'Vietnamese', 'name_native' => 'Tiếng Việt', ...$ltrLatin],
            ['code' => 'th', 'name_en' => 'Thai', 'name_native' => 'ภาษาไทย', 'script' => 'Thai', 'direction' => 'ltr'],
            ['code' => 'id', 'name_en' => 'Indonesian', 'name_native' => 'Bahasa Indonesia', ...$ltrLatin],
            ['code' => 'ms', 'name_en' => 'Malay', 'name_native' => 'Bahasa Melayu', ...$ltrLatin],
            ['code' => 'uk', 'name_en' => 'Ukrainian', 'name_native' => 'Українська', ...$ltrCyrillic],
            ['code' => 'cs', 'name_en' => 'Czech', 'name_native' => 'Čeština', ...$ltrLatin],
            ['code' => 'ro', 'name_en' => 'Romanian', 'name_native' => 'Română', ...$ltrLatin],
            ['code' => 'hu', 'name_en' => 'Hungarian', 'name_native' => 'Magyar', ...$ltrLatin],
            ['code' => 'el', 'name_en' => 'Greek', 'name_native' => 'Ελληνικά', 'script' => 'Greek', 'direction' => 'ltr'],
            ['code' => 'he', 'name_en' => 'Hebrew', 'name_native' => 'עברית', 'script' => 'Hebrew', 'direction' => 'rtl'],
            ['code' => 'ur', 'name_en' => 'Urdu', 'name_native' => 'اردو', ...$rtlArabic],
            ['code' => 'sw', 'name_en' => 'Swahili', 'name_native' => 'Kiswahili', ...$ltrLatin],
            ['code' => 'ca', 'name_en' => 'Catalan', 'name_native' => 'Català', ...$ltrLatin],
            ['code' => 'hr', 'name_en' => 'Croatian', 'name_native' => 'Hrvatski', ...$ltrLatin],
            ['code' => 'sk', 'name_en' => 'Slovak', 'name_native' => 'Slovenčina', ...$ltrLatin],
            ['code' => 'bg', 'name_en' => 'Bulgarian', 'name_native' => 'Български', ...$ltrCyrillic],
            ['code' => 'sr', 'name_en' => 'Serbian', 'name_native' => 'Српски', ...$ltrCyrillic],
            ['code' => 'lt', 'name_en' => 'Lithuanian', 'name_native' => 'Lietuvių', ...$ltrLatin],
            ['code' => 'lv', 'name_en' => 'Latvian', 'name_native' => 'Latviešu', ...$ltrLatin],
        ];

        foreach ($languages as $lang) {
            Language::query()->updateOrCreate(
                ['code' => $lang['code']],
                $lang,
            );
        }
    }
}
