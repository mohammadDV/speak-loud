<?php

namespace App\Support;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><ul><ol><li><a><h2><h3><h4><blockquote>';

    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $cleaned = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $cleaned = preg_replace('#<(script|style|iframe|object|embed)[^>]*/?>#is', '', $cleaned) ?? $cleaned;
        $cleaned = strip_tags($cleaned, self::ALLOWED_TAGS);

        $cleaned = preg_replace_callback(
            '/<a\s+([^>]*?)>/i',
            function (array $matches): string {
                $attrs = $matches[1];
                $href = null;

                if (preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $attrs, $hrefMatch)) {
                    $candidate = trim($hrefMatch[2]);
                    if (preg_match('/^(https?:\/\/|mailto:|\/|#)/i', $candidate)
                        && ! preg_match('/^\s*javascript:/i', $candidate)
                    ) {
                        $href = htmlspecialchars($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }

                return $href !== null ? '<a href="'.$href.'" rel="noopener noreferrer">' : '<a>';
            },
            $cleaned
        ) ?? $cleaned;

        $cleaned = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\son\w+\s*=\s*[^\s>]+/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/javascript\s*:/i', '', $cleaned) ?? $cleaned;

        return $cleaned;
    }
}
