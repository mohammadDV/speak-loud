<?php

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Learning\Services\ArticleQuizService;
use App\Support\Seo;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{state, mount, title, computed};

state([
    'levels' => [],
    'translation_language' => 'en',
    'error_message' => null,
    'feedback' => null,
    'last_wrong_article' => null,
    'tremor_token' => 0,
    'animation_token' => 0,
    'selected_level' => null,
]);

// Keep play progress server-owned so clients cannot skip words or clear wrong marks.
state([
    'phase' => 'level',
    'cards' => [],
    'quiz_sense_ids' => [],
    'current_index' => 0,
    'wrong_articles' => [],
])->locked();

mount(function (ArticleQuizService $quiz) {
    Seo::share([
        'seoTitle' => 'Article Hug',
        'seoDescription' => 'Pick a level, then choose der, die, or das for German nouns. Correct articles fly in as birds and hug the word.',
        'seoUrl' => route('learn.article-hug'),
    ]);

    $this->levels = $quiz->levels()->map(fn ($level) => [
        'code' => $level->code,
        'name' => $level->name,
    ])->all();

    $availableCodes = $quiz->translationLanguages()->pluck('code');
    $this->translation_language = (string) ($availableCodes->first() ?? '');
});

title(fn () => Seo::pageTitle('Article Hug'));

$loadDeck = function (ArticleQuizService $quiz): bool {
    $this->error_message = null;
    $this->feedback = null;
    $this->wrong_articles = [];
    $this->last_wrong_article = null;

    if ($this->translation_language === '') {
        $this->cards = [];
        $this->quiz_sense_ids = [];
        $this->current_index = 0;
        $this->error_message = 'Not enough nouns are available yet.';

        return false;
    }

    $throttleKey = 'article-hug-deck|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->error_message = 'Too many refreshes. Try again in '.$seconds.'s.';

        return false;
    }

    $levelCodes = $this->selected_level === null || $this->selected_level === 'all'
        ? []
        : [$this->selected_level];

    try {
        $built = $quiz->buildQuiz(
            20,
            $levelCodes,
            [],
            $this->translation_language,
            auth()->user(),
        );
    } catch (ValidationException $e) {
        $this->cards = [];
        $this->quiz_sense_ids = [];
        $this->current_index = 0;
        $this->error_message = collect($e->errors())->flatten()->first()
            ?? 'Not enough nouns for this level.';

        return false;
    }

    RateLimiter::hit($throttleKey, 60);

    $this->cards = $quiz->toQuizPromptCards($built);
    $this->quiz_sense_ids = array_map(
        static fn (array $card): int => (int) $card['sense_id'],
        $this->cards,
    );
    $this->current_index = 0;
    $this->animation_token++;

    return true;
};

$chooseLevel = function (string $level, ArticleQuizService $quiz) {
    $allowed = collect($this->levels)->pluck('code')->push('all');
    if (! $allowed->contains($level)) {
        return;
    }

    $this->selected_level = $level;

    if (! $this->loadDeck($quiz)) {
        return;
    }

    $this->phase = 'play';
};

$resetLevel = function () {
    $this->phase = 'level';
    $this->selected_level = null;
    $this->cards = [];
    $this->quiz_sense_ids = [];
    $this->current_index = 0;
    $this->feedback = null;
    $this->wrong_articles = [];
    $this->last_wrong_article = null;
    $this->error_message = null;
    $this->animation_token++;
};

$chooseArticle = function (string $article, ArticleQuizService $quiz) {
    $lockedByCorrect = is_array($this->feedback) && ($this->feedback['is_correct'] ?? false);
    if ($this->phase !== 'play' || $lockedByCorrect) {
        return;
    }

    $article = strtolower(trim($article));
    if (! GermanArticle::isValid($article)) {
        return;
    }

    if (in_array($article, $this->wrong_articles, true)) {
        return;
    }

    $senseIds = array_values(array_map(
        static fn (mixed $id): int => (int) $id,
        $this->quiz_sense_ids,
    ));
    $senseId = (int) ($senseIds[$this->current_index] ?? 0);
    if ($senseId <= 0 || ! in_array($senseId, $senseIds, true)) {
        $this->error_message = 'This word is no longer available. Pick a level again.';
        $this->resetLevel();

        return;
    }

    $throttleKey = 'article-hug-answer|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($throttleKey, 90)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->error_message = 'Slow down — try again in '.$seconds.'s.';

        return;
    }

    // Lightweight article lookup — avoid full quiz hydration (examples/collocations/flags).
    $resolved = $quiz->resolveArticlesBySenseId([$senseId]);
    $correctArticle = $resolved[$senseId]['article'] ?? null;

    RateLimiter::hit($throttleKey, 60);

    if ($correctArticle === null) {
        $this->error_message = 'This word is no longer available. Pick a level again.';
        $this->resetLevel();

        return;
    }

    if ($article !== $correctArticle) {
        $this->wrong_articles = array_values(array_unique([
            ...$this->wrong_articles,
            $article,
        ]));
        $this->last_wrong_article = $article;
        $this->tremor_token++;
        $this->feedback = null;

        return;
    }

    $this->last_wrong_article = null;
    $this->feedback = [
        'is_correct' => true,
        'selected_article' => $article,
        'correct_article' => $correctArticle,
    ];
    $this->animation_token++;

    $token = (int) $this->animation_token;
    $this->js("setTimeout(() => \$wire.nextWord({$token}), 1900)");
};

$nextWord = function (int $token, ArticleQuizService $quiz) {
    if ($this->phase !== 'play' || $token !== (int) $this->animation_token || $this->feedback === null) {
        return;
    }

    if (! ($this->feedback['is_correct'] ?? false)) {
        return;
    }

    $next = $this->current_index + 1;
    if ($next >= count($this->quiz_sense_ids)) {
        $this->loadDeck($quiz);

        return;
    }

    $this->current_index = $next;
    $this->feedback = null;
    $this->wrong_articles = [];
    $this->last_wrong_article = null;
    $this->animation_token++;
};

$currentCard = computed(function (): ?array {
    if ($this->phase !== 'play') {
        return null;
    }

    return $this->cards[$this->current_index] ?? null;
});

?>

<div class="article-hug-stage min-h-[70vh] max-w-3xl mx-auto px-4 py-8 md:py-12 pb-28 md:pb-12 flex flex-col justify-center">
    @if ($error_message)
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 text-center" role="alert">
            {{ $error_message }}
        </div>
    @endif

    @if ($phase === 'level')
        <div class="article-hug-sky relative overflow-hidden rounded-[2rem] border border-[#3D2B1F]/8 px-5 py-10 md:px-10 md:py-14">
            <span class="article-hug-blob article-hug-blob--1" aria-hidden="true"></span>
            <span class="article-hug-blob article-hug-blob--2" aria-hidden="true"></span>
            <span class="article-hug-blob article-hug-blob--3" aria-hidden="true"></span>

            <div class="relative space-y-6">
                <p class="text-center text-lg md:text-xl font-semibold text-[#3D2B1F] tracking-tight">
                    Train your brain with the article
                </p>

                <div class="flex flex-wrap justify-center gap-3">
                <button
                    type="button"
                    wire:click="chooseLevel('all')"
                    class="article-hug-level article-hug-level--on rounded-full px-6 py-3 text-base font-bold"
                >
                    All
                </button>
                @foreach ($levels as $level)
                    <button
                        type="button"
                        wire:click="chooseLevel('{{ $level['code'] }}')"
                        class="article-hug-level rounded-full px-6 py-3 text-base font-bold border border-[#3D2B1F]/10 bg-white text-[#3D2B1F]"
                    >
                        {{ $level['code'] }}
                    </button>
                @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($phase === 'play')
        @php
            $card = $this->currentCard;
            $isCorrect = is_array($feedback) && ($feedback['is_correct'] ?? false);
            $selected = $isCorrect ? (string) ($feedback['selected_article'] ?? '') : '';
            $correctArticle = $isCorrect ? (string) ($feedback['correct_article'] ?? '') : '';
            $wrongArticles = is_array($wrong_articles) ? $wrong_articles : [];
        @endphp

        @if ($card)
            <section class="space-y-8" wire:key="hug-word-{{ $animation_token }}-{{ $card['sense_id'] }}">
                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="resetLevel"
                        class="rounded-full border border-[#3D2B1F]/12 bg-white px-4 py-2 text-sm font-semibold text-[#3D2B1F]/65 hover:text-[#3D2B1F] hover:border-[#3D2B1F]/25 transition"
                    >
                        Reset
                    </button>
                </div>

                @php
                    $wingPath = 'M2 14C4 6 10 2 18 3C14 7 13 12 14 18C9 17 4 17 2 14Z';
                @endphp

                <div class="article-hug-sky relative overflow-hidden rounded-[2rem] border border-[#3D2B1F]/8 px-4 py-14 md:px-8 md:py-20 text-center">
                    <span class="article-hug-blob article-hug-blob--1" aria-hidden="true"></span>
                    <span class="article-hug-blob article-hug-blob--2" aria-hidden="true"></span>

                    <div class="article-hug-arena">
                        <div @class([
                            'article-hug-noun',
                            'article-hug-noun--embraced' => $isCorrect,
                        ])>
                            <span class="relative z-[2] text-3xl md:text-5xl font-extrabold tracking-tight text-[#3D2B1F]">
                                {{ $card['lemma'] }}
                            </span>
                        </div>

                        @if ($isCorrect && $correctArticle !== '')
                            <div
                                class="article-hug-bird article-hug-bird--flying"
                                data-article="{{ $correctArticle }}"
                                aria-hidden="true"
                            >
                                <svg class="article-hug-bird__wing article-hug-bird__wing--left" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="{{ $wingPath }}" />
                                </svg>
                                <svg class="article-hug-bird__wing article-hug-bird__wing--right" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="{{ $wingPath }}" />
                                </svg>
                                <span class="article-hug-bird__eye article-hug-bird__eye--left"></span>
                                <span class="article-hug-bird__eye article-hug-bird__eye--right"></span>
                                <span class="article-hug-bird__beak"></span>
                                <span class="article-hug-bird__body">
                                    <span class="article-hug-bird__label">{{ $correctArticle }}</span>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2.5 md:gap-4" role="group" aria-label="Choose der, die, or das">
                    @foreach (['der', 'die', 'das'] as $option)
                        @php
                            $isWrong = in_array($option, $wrongArticles, true);
                            $isTrembling = $isWrong && $last_wrong_article === $option;
                            $isWinner = $isCorrect && $correctArticle === $option;
                            $isLaunch = $isCorrect && $selected === $option;
                            $dim = $isCorrect && ! $isWinner;
                        @endphp
                        <button
                            type="button"
                            wire:key="article-btn-{{ $option }}-{{ $isTrembling ? $tremor_token : 'idle' }}"
                            wire:click="chooseArticle('{{ $option }}')"
                            @disabled($isCorrect || $isWrong)
                            @class([
                                'article-hug-btn article-hug-btn--'.$option,
                                'rounded-2xl md:rounded-3xl px-2 py-4 md:py-5 text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-[#FF8C42]/40',
                                'article-hug-btn--winner' => $isWinner,
                                'article-hug-btn--wrong' => $isWrong,
                                'article-hug-btn--tremor' => $isTrembling,
                                'article-hug-btn--launch' => $isLaunch,
                                'article-hug-btn--dim' => $dim,
                            ])
                        >
                            <svg class="article-hug-btn-wing article-hug-btn-wing--left" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="{{ $wingPath }}" />
                            </svg>
                            <svg class="article-hug-btn-wing article-hug-btn-wing--right" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="{{ $wingPath }}" />
                            </svg>
                            <div class="article-hug-face" aria-hidden="true">
                                <span class="article-hug-eye"></span>
                                <span class="article-hug-eye"></span>
                            </div>
                            <span class="block text-xl md:text-3xl font-extrabold leading-none">{{ $option }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</div>
