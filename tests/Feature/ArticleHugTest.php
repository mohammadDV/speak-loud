<?php

use App\Domain\Dictionary\Models\Sense;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Livewire\Volt\Volt;

/**
 * @param  list<Sense>  $senses
 * @return array<int, string>
 */
function articleHugArticlesBySenseId(array $senses): array
{
    $map = [];
    foreach ($senses as $sense) {
        $map[$sense->id] = strtolower((string) $sense->germanNounDetail->article->value);
    }

    return $map;
}

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

test('guests see only the level chooser on first load', function () {
    createPublishedGermanNouns(12);

    $this->get(route('learn.article-hug'))
        ->assertOk()
        ->assertSee('Train your brain with the article')
        ->assertSee('All')
        ->assertSee('A1')
        ->assertDontSee('Classic quiz')
        ->assertDontSee('Endless practice')
        ->assertDontSee('Translation')
        ->assertDontSee('Reset')
        ->assertDontSee('chooseArticle');
});

test('choosing a level starts play and grades a correct hug', function () {
    $senses = createPublishedGermanNouns(12);
    $articlesBySenseId = articleHugArticlesBySenseId($senses);

    $component = Volt::test('learn.article-hug')
        ->assertSet('phase', 'level')
        ->call('chooseLevel', 'A1')
        ->assertSet('phase', 'play')
        ->assertSet('selected_level', 'A1')
        ->assertSet('feedback', null)
        ->assertSee('Reset')
        ->assertSee('der')
        ->assertSee('die')
        ->assertSee('das');

    $senseId = (int) $component->get('quiz_sense_ids')[0];
    $correct = $articlesBySenseId[$senseId];

    expect($component->get('cards')[0])->not->toHaveKey('article');

    $component
        ->call('chooseArticle', $correct)
        ->assertSet('feedback.is_correct', true)
        ->assertSet('feedback.correct_article', $correct);
});

test('wrong article turns red and leaves room to choose another option', function () {
    $senses = createPublishedGermanNouns(12);
    $articlesBySenseId = articleHugArticlesBySenseId($senses);

    $component = Volt::test('learn.article-hug')
        ->call('chooseLevel', 'all');

    $senseId = (int) $component->get('quiz_sense_ids')[0];
    $correct = $articlesBySenseId[$senseId];
    $wrong = $correct === 'der' ? 'die' : 'der';
    $firstLemma = $component->get('cards')[0]['lemma'];

    $component
        ->call('chooseArticle', $wrong)
        ->assertSet('feedback', null)
        ->assertSet('current_index', 0)
        ->assertSet('wrong_articles', [$wrong])
        ->assertSet('last_wrong_article', $wrong)
        ->assertSee('article-hug-btn--wrong', false)
        ->assertSee('article-hug-btn--tremor', false);

    expect($component->get('cards')[0]['lemma'])->toBe($firstLemma);

    $component
        ->call('chooseArticle', $correct)
        ->assertSet('feedback.is_correct', true)
        ->assertSet('feedback.correct_article', $correct);
});

test('play cards never expose articles before a correct answer', function () {
    createPublishedGermanNouns(12);

    $component = Volt::test('learn.article-hug')
        ->call('chooseLevel', 'A1');

    foreach ($component->get('cards') as $card) {
        expect($card)->toHaveKeys(['sense_id', 'lemma'])
            ->and($card)->not->toHaveKey('article')
            ->and($card)->not->toHaveKey('translation');
    }
});

test('reset returns to level selection', function () {
    createPublishedGermanNouns(12);

    Volt::test('learn.article-hug')
        ->call('chooseLevel', 'A1')
        ->assertSet('phase', 'play')
        ->call('resetLevel')
        ->assertSet('phase', 'level')
        ->assertSet('selected_level', null)
        ->assertSet('quiz_sense_ids', [])
        ->assertSet('wrong_articles', [])
        ->assertSee('All')
        ->assertDontSee('Reset');
});

test('deck refills endlessly while playing', function () {
    $senses = createPublishedGermanNouns(20);
    $articlesBySenseId = articleHugArticlesBySenseId($senses);

    $component = Volt::test('learn.article-hug')
        ->call('chooseLevel', 'all');

    $senseIds = $component->get('quiz_sense_ids');
    $count = count($senseIds);
    expect($count)->toBeGreaterThanOrEqual(5);

    foreach ($senseIds as $senseId) {
        $correct = $articlesBySenseId[(int) $senseId];
        $component->call('chooseArticle', $correct);
        $token = (int) $component->get('animation_token');
        $component->call('nextWord', $token);
    }

    $component
        ->assertSet('phase', 'play')
        ->assertSet('feedback', null)
        ->assertSet('quiz_sense_ids', fn ($ids) => count($ids) >= 5);
});
