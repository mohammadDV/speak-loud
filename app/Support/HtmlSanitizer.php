<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizer
{
    private static ?SymfonyHtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): SymfonyHtmlSanitizer
    {
        return self::$sanitizer ??= new SymfonyHtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('a', ['href'])
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('h4')
                ->allowElement('blockquote')
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->allowRelativeLinks()
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
        );
    }
}
