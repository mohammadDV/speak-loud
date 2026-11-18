<?php

namespace App\Domain\Learning\Services;

use Illuminate\Support\Str;

final class LessonMarkdownRenderer
{
    /** @var list<string> */
    public const RTL_LANGUAGES = ['fa', 'ar'];

    public function isRtl(string $languageCode): bool
    {
        return in_array($languageCode, self::RTL_LANGUAGES, true);
    }

    public function toHtml(string $markdown, string $languageCode = 'en'): string
    {
        $cleaned = $this->normalizeTeachingMarkdown($markdown);

        $html = Str::markdown($cleaned, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        // Keep German paradigm tables LTR even inside RTL lesson pages.
        $html = preg_replace(
            '/<table>/',
            '<div class="lesson-table-wrap"><table dir="ltr">',
            $html,
        ) ?? $html;

        $html = preg_replace('/<\/table>/', '</table></div>', $html) ?? $html;

        return $html;
    }

    public function normalizeTeachingMarkdown(string $markdown): string
    {
        $replacements = [
            '$\\rightarrow$' => '→',
            '$\\to$' => '→',
            '$e \\rightarrow i$' => 'e → i',
            '$e \\rightarrow i / ie$' => 'e → i / ie',
            '$a \\rightarrow ä$' => 'a → ä',
            '$au \\rightarrow äu$' => 'au → äu',
            '$e \\rightarrow i$ or $ie$' => 'e → i or ie',
            '$a \\rightarrow ä$ (and $au \\rightarrow äu$)' => 'a → ä (and au → äu)',
        ];

        $normalized = str_replace(array_keys($replacements), array_values($replacements), $markdown);

        // Catch remaining simple math arrows: $foo \rightarrow bar$
        $normalized = preg_replace(
            '/\$([^$]*?)\\\\rightarrow([^$]*?)\$/u',
            '$1→$2',
            $normalized,
        ) ?? $normalized;

        // Strip leftover $...$ wrappers that only contain plain text / arrows.
        $normalized = preg_replace('/\$([^$]+)\$/u', '$1', $normalized) ?? $normalized;

        return $normalized;
    }
}
