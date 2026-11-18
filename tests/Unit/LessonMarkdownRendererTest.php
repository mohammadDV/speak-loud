<?php

use App\Domain\Learning\Services\LessonMarkdownRenderer;

test('lesson markdown replaces latex arrows and renders tables', function () {
    $renderer = new LessonMarkdownRenderer;

    $html = $renderer->toHtml(<<<'MD'
| Infinitive | Example |
| --- | --- |
| *fahren* | er **fährt** |

*fahren* $\rightarrow$ er **fährt** (to drive/go)

Group: $e \rightarrow i$ or $ie$
MD);

    expect($html)->not->toContain('$')
        ->and($html)->not->toContain('\\rightarrow')
        ->and($html)->toContain('→')
        ->and($html)->toContain('lesson-table-wrap')
        ->and($html)->toContain('<table dir="ltr">')
        ->and($html)->toContain('fährt');
});

test('rtl languages are detected for fa and ar only', function () {
    $renderer = new LessonMarkdownRenderer;

    expect($renderer->isRtl('fa'))->toBeTrue()
        ->and($renderer->isRtl('ar'))->toBeTrue()
        ->and($renderer->isRtl('en'))->toBeFalse()
        ->and($renderer->isRtl('tr'))->toBeFalse();
});
