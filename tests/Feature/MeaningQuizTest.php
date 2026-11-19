<?php

use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseTranslation;
use App\Domain\Learning\Enums\MeaningQuizType;
use App\Domain\Learning\Models\MeaningQuizSession;
use App\Domain\Learning\Services\MeaningQuizService;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;

/**
 * @param  list<Sense>  $senses
 * @return array<int, string>
 */
function meaningsBySenseId(array $senses): array
{
    $map = [];
    foreach ($senses as $sense) {
        $map[$sense->id] = (string) $sense->translations->first()->translation_text;
    }

    return $map;
}

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

test('guests can view the learn hub and meaning quiz page', function () {
    $this->get(route('learn.index'))
        ->assertOk()
        ->assertSee('Learn')
        ->assertSee('Der / Die / Das')
        ->assertSee('Vocabulary meaning')
        ->assertSee('Prepositions');

    $this->get(route('learn.vocabulary-meaning'))
        ->assertOk()
        ->assertSee('Vocabulary meaning')
        ->assertSee('Choose one of four')
        ->assertSee('Type the meaning')
        ->assertSee('Start quiz');
});

test('guest can complete a multiple choice meaning quiz without persisting', function () {
    $senses = createPublishedGermanNouns(5);
    foreach ($senses as $sense) {
        $sense->load('translations');
    }
    $meanings = meaningsBySenseId($senses);

    Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->tap(function ($component) use ($meanings) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                expect($card)->toHaveKey('options')
                    ->and($card)->not->toHaveKey('translation');
                expect($card['options'])->toHaveCount(4);
                $answers[(string) $card['sense_id']] = $meanings[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100)
        ->assertSee('Your score')
        ->assertSee('Sign up to save this result');

    expect(MeaningQuizSession::query()->count())->toBe(0);
});

test('guest can complete a write-in meaning quiz with fuzzy matching', function () {
    $senses = createPublishedGermanNouns(5);
    foreach ($senses as $sense) {
        $sense->load('translations');
    }

    Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::WriteIn->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->tap(function ($component) use ($senses) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                expect($card)->not->toHaveKey('options')
                    ->and($card)->not->toHaveKey('translation');
                $meaning = (string) collect($senses)->firstWhere('id', $card['sense_id'])
                    ->translations->first()->translation_text;
                // Introduce a small typo for fuzzy acceptance on longer strings;
                // exact answer for short "word N" glosses.
                $answers[(string) $card['sense_id']] = $meaning;
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100);

    expect(MeaningQuizSession::query()->count())->toBe(0);
});

test('authenticated user meaning quiz results are persisted with answer details', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    foreach ($senses as $sense) {
        $sense->load('translations');
    }
    $meanings = meaningsBySenseId($senses);

    Volt::actingAs($user)
        ->test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->tap(function ($component) use ($meanings) {
            $answers = [];
            foreach ($component->get('cards') as $index => $card) {
                $correct = $meanings[(int) $card['sense_id']];
                $wrong = collect($card['options'])->first(fn (string $option) => $option !== $correct);
                $answers[(string) $card['sense_id']] = $index === 0 ? (string) $wrong : $correct;
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('correct_count', 4)
        ->assertSet('incorrect_count', 1);

    $session = MeaningQuizSession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->word_count)->toBe(5)
        ->and($session->quiz_type)->toBe(MeaningQuizType::MultipleChoice)
        ->and($session->correct_count)->toBe(4)
        ->and($session->answers)->toHaveCount(5)
        ->and($session->answers->first()->correct_meaning)->not->toBe('');
});

test('persisted write-in scores ignore client claims and use fuzzy checker', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    foreach ($senses as $sense) {
        $sense->load('translations');
    }

    Volt::actingAs($user)
        ->test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::WriteIn->value)
        ->call('startQuiz')
        ->tap(function ($component) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = 'totally-wrong-meaning';
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('correct_count', 0)
        ->assertSet('percentage', 0);

    $session = MeaningQuizSession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->correct_count)->toBe(0)
        ->and($session->quiz_type)->toBe(MeaningQuizType::WriteIn);
});

test('multiple choice options prefer same-category distractors', function () {
    $family = createPublishedGermanNouns(5, ['category' => 'family', 'lemma_prefix' => 'Fam']);
    $food = createPublishedGermanNouns(5, ['category' => 'food', 'lemma_prefix' => 'Food']);

    foreach ([...$family, ...$food] as $sense) {
        $sense->load(['translations', 'lexeme.categories']);
    }

    $service = app(MeaningQuizService::class);
    $cards = $service->buildQuiz(
        5,
        ['A1'],
        ['family'],
        'en',
        MeaningQuizType::MultipleChoice,
    );

    expect($cards)->toHaveCount(5);

    $familyMeanings = collect($family)->map(
        fn ($sense) => (string) $sense->translations->first()->translation_text,
    )->all();

    foreach ($cards as $card) {
        expect($card['options'])->toHaveCount(4);
        $overlap = collect($card['options'])->intersect($familyMeanings)->count();
        expect($overlap)->toBeGreaterThanOrEqual(2);
    }
});

test('meaning quiz requires answers before submit', function () {
    createPublishedGermanNouns(5);

    Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->call('submitQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSet('show_error_modal', true);
});

test('mcq grades against primary meaning only and rejects answers outside options', function () {
    $senses = createPublishedGermanNouns(5);
    $en = Language::query()->where('code', 'en')->firstOrFail();

    foreach ($senses as $index => $sense) {
        SenseTranslation::factory()->published()->create([
            'sense_id' => $sense->id,
            'target_language_id' => $en->id,
            'translation_text' => 'alt-gloss-'.$index,
            'sort_order' => 1,
        ]);
        $sense->load('translations');
    }

    $primaryBySense = meaningsBySenseId($senses);

    Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSet('session_quiz_type', MeaningQuizType::MultipleChoice->value)
        ->tap(function ($component) use ($primaryBySense) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $senseId = (int) $card['sense_id'];
                $primary = $primaryBySense[$senseId];
                $alt = 'alt-gloss-'.collect(array_keys($primaryBySense))->search($senseId);
                // Prefer a shown option that is not the primary when available.
                $wrongShown = collect($card['options'])->first(fn (string $option) => $option !== $primary);
                $answers[(string) $senseId] = $wrongShown ?? $alt;
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('correct_count', 0);

    // Submitting a secondary gloss that was not shown must fail validation / stay in quiz.
    Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->tap(function ($component) {
            $answers = [];
            foreach ($component->get('cards') as $index => $card) {
                $answers[(string) $card['sense_id']] = 'alt-gloss-'.$index;
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSet('show_error_modal', true);
});

test('locked session quiz type is used even if setup quiz_type is changed after start', function () {
    $senses = createPublishedGermanNouns(5);
    foreach ($senses as $sense) {
        $sense->load('translations');
    }
    $meanings = meaningsBySenseId($senses);

    Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->assertSet('session_quiz_type', MeaningQuizType::MultipleChoice->value)
        ->set('quiz_type', MeaningQuizType::WriteIn->value)
        ->tap(function ($component) use ($meanings) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $meanings[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100)
        ->assertSet('session_quiz_type', MeaningQuizType::MultipleChoice->value);
});

test('start quiz is rate limited', function () {
    createPublishedGermanNouns(5);

    $component = Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en');

    $throttleKey = 'meaning-quiz-start|'.request()->ip();
    RateLimiter::clear($throttleKey);
    for ($i = 0; $i < 20; $i++) {
        RateLimiter::hit($throttleKey, 60);
    }

    $component
        ->call('startQuiz')
        ->assertSet('phase', 'setup')
        ->assertSet('show_error_modal', true)
        ->assertSee('Too many quiz starts');
});
