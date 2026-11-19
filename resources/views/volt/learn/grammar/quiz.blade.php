<?php

use App\Domain\Learning\Actions\SaveGrammarQuizResult;
use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Services\GrammarQuizService;
use App\Domain\Learning\Services\LessonMarkdownRenderer;
use App\Support\Seo;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{mount, state, title};

state([
    'lesson_ids' => [],
    'course_code' => null,
    'word_count' => 10,
    'translation_language' => 'en',
    'quiz_type' => GrammarQuizType::MultipleChoice->value,
    'languages' => [],
    'answers' => [],
    'results' => [],
    'correct_count' => 0,
    'incorrect_count' => 0,
    'percentage' => 0,
    'saved_session_id' => null,
    'error_message' => null,
    'show_error_modal' => false,
    'scroll_to_id' => null,
]);

state([
    'phase' => 'setup',
    'cards' => [],
    'quiz_question_ids' => [],
    'session_quiz_type' => null,
    'session_translation_language' => null,
    'session_lesson_ids' => [],
])->locked();

mount(function () {
    Seo::share([
        'seoTitle' => 'Grammar quiz',
        'seoDescription' => 'Practice German grammar with multiple-choice or fill-in-the-blank questions.',
        'seoUrl' => route('learn.grammar.quiz'),
    ]);

    $this->languages = collect(GrammarQuizService::UI_LANGUAGES)
        ->map(fn (string $code): array => [
            'code' => $code,
            'label' => strtoupper($code),
        ])
        ->all();

    $lang = request()->query('lang');
    if (is_string($lang) && in_array($lang, GrammarQuizService::UI_LANGUAGES, true)) {
        $this->translation_language = $lang;
    }

    $course = request()->query('course');
    if (is_string($course) && $course !== '') {
        $this->course_code = $course;
    }

    $lessons = request()->query('lessons');
    if (is_string($lessons) && $lessons !== '') {
        $this->lesson_ids = array_values(array_filter(array_map('intval', explode(',', $lessons))));
    } elseif (is_array($lessons)) {
        $this->lesson_ids = array_values(array_filter(array_map('intval', $lessons)));
    }

    $count = request()->integer('count');
    if ($count >= 5 && $count <= 50) {
        $this->word_count = $count;
    }

    $type = request()->query('type');
    if (is_string($type) && GrammarQuizType::isValid($type)) {
        $this->quiz_type = $type;
    }
});

title(fn () => Seo::pageTitle('Grammar quiz'));

$promptLanguage = function (): string {
    if (is_string($this->session_translation_language) && $this->session_translation_language !== '') {
        return $this->session_translation_language;
    }

    return (string) $this->translation_language;
};

$promptsAreRtl = function (): bool {
    return app(LessonMarkdownRenderer::class)->isRtl($this->promptLanguage());
};

$showError = function (string $message, ?string $scrollToId = null) {
    $this->error_message = $message;
    $this->show_error_modal = true;
    $this->scroll_to_id = $scrollToId;
};

$dismissError = function () {
    $this->show_error_modal = false;
    $this->error_message = null;
    $this->scroll_to_id = null;
};

$resolvedQuizType = function (): GrammarQuizType {
    return GrammarQuizType::tryFrom((string) $this->quiz_type) ?? GrammarQuizType::MultipleChoice;
};

$startQuiz = function (GrammarQuizService $quiz) {
    $startThrottleKey = 'grammar-quiz-start|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($startThrottleKey, 20)) {
        $seconds = RateLimiter::availableIn($startThrottleKey);
        $this->showError('Too many quiz starts. Please try again in '.$seconds.' seconds.');

        return;
    }

    if ($this->phase !== 'setup') {
        $this->showError('Finish or reset the current quiz before starting again.');

        return;
    }

    $this->error_message = null;
    $this->show_error_modal = false;

    try {
        $this->validate([
            'lesson_ids' => ['required', 'array', 'min:1'],
            'lesson_ids.*' => ['integer'],
            'word_count' => ['required', 'integer', 'min:5', 'max:50'],
            'translation_language' => ['required', 'string', 'in:en,es,fa,ar,tr'],
            'quiz_type' => ['required', 'in:multiple_choice,fill_blank'],
        ], [
            'lesson_ids.required' => 'Select at least one lesson.',
            'lesson_ids.min' => 'Select at least one lesson.',
            'word_count.min' => 'Choose between 5 and 50 questions.',
            'word_count.max' => 'Choose between 5 and 50 questions.',
        ]);

        // Keep answered cards in a local variable only — never put them in Livewire state.
        $fullCards = $quiz->buildQuiz(
            array_values(array_map('intval', $this->lesson_ids)),
            (int) $this->word_count,
            $this->translation_language,
            $this->resolvedQuizType(),
        );
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Something went wrong. Please check your choices and try again.';
        $this->cards = [];
        $this->showError($message, 'quiz-setup');

        return;
    }

    RateLimiter::hit($startThrottleKey, 60);

    $quizType = $this->resolvedQuizType();
    $this->session_quiz_type = $quizType->value;
    $this->session_translation_language = $this->translation_language;
    $this->session_lesson_ids = array_values(array_map('intval', $this->lesson_ids));
    $this->cards = $quiz->toQuizPromptCards($fullCards);
    $this->quiz_question_ids = array_map(
        static fn (array $card): int => (int) $card['question_id'],
        $this->cards,
    );

    $this->answers = [];
    foreach ($this->quiz_question_ids as $questionId) {
        $this->answers[(string) $questionId] = '';
    }

    $this->results = [];
    $this->saved_session_id = null;
    $this->phase = 'quiz';
};

$backToSetup = function () {
    $this->phase = 'setup';
    $this->cards = [];
    $this->quiz_question_ids = [];
    $this->session_quiz_type = null;
    $this->session_translation_language = null;
    $this->session_lesson_ids = [];
    $this->answers = [];
    $this->results = [];
    $this->error_message = null;
    $this->show_error_modal = false;
};

$submitQuiz = function (SaveGrammarQuizResult $save, GrammarQuizService $quiz) {
    if ($this->phase !== 'quiz') {
        $this->showError('Start the quiz before submitting answers.');

        return;
    }

    $questionIds = array_values(array_map(
        static fn (mixed $id): int => (int) $id,
        $this->quiz_question_ids,
    ));

    if ($questionIds === []) {
        $this->showError('This quiz session is no longer valid. Please start again.');
        $this->backToSetup();

        return;
    }

    $throttleKey = 'grammar-quiz-submit|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->showError('Too many quiz submissions. Please try again in '.$seconds.' seconds.');

        return;
    }

    $quizType = GrammarQuizType::tryFrom((string) $this->session_quiz_type)
        ?? $this->resolvedQuizType();

    $optionsByQuestionId = [];
    foreach ($this->cards as $card) {
        $questionId = (int) $card['question_id'];
        $optionsByQuestionId[$questionId] = array_values(array_map(
            static fn (mixed $option): string => (string) $option,
            $card['options'] ?? [],
        ));
    }

    $translationLanguage = is_string($this->session_translation_language) && $this->session_translation_language !== ''
        ? $this->session_translation_language
        : $this->translation_language;

    $resolved = $quiz->resolveQuestionsById($questionIds, $translationLanguage);
    $cardsForGrading = [];
    foreach ($questionIds as $questionId) {
        if (! isset($resolved[$questionId])) {
            $this->showError('One or more quiz questions are no longer available. Please start a new quiz.');

            return;
        }

        $cardsForGrading[] = [
            'question_id' => $questionId,
            'prompt' => (string) $resolved[$questionId]['prompt'],
            'answer' => (string) $resolved[$questionId]['answer'],
            'options' => $optionsByQuestionId[$questionId] ?? [],
        ];
    }

    try {
        $this->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string', 'max:255'],
        ], [
            'answers.*.required' => $quizType === GrammarQuizType::MultipleChoice
                ? 'Choose one answer for every question.'
                : 'Fill in every blank.',
        ]);

        if ($quizType === GrammarQuizType::MultipleChoice) {
            foreach ($questionIds as $questionId) {
                $selected = trim((string) ($this->answers[(string) $questionId] ?? ''));
                $options = $optionsByQuestionId[$questionId] ?? [];
                if ($options === [] || ! in_array($selected, $options, true)) {
                    throw ValidationException::withMessages([
                        'answers.'.$questionId => 'Choose one of the shown answers for every question.',
                    ]);
                }
            }
        }
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Answer every question before submitting.';
        $this->showError($message);

        return;
    }

    $answersForGrading = [];
    foreach ($questionIds as $position => $questionId) {
        $answersForGrading[] = [
            'question_id' => $questionId,
            'selected_answer' => (string) ($this->answers[(string) $questionId] ?? ''),
            'position' => $position + 1,
        ];
    }

    try {
        if (auth()->check()) {
            $session = $save->execute(auth()->user(), [
                'lesson_ids' => array_values(array_map('intval', $this->session_lesson_ids ?: $this->lesson_ids)),
                'word_count' => count($questionIds),
                'translation_language_code' => $translationLanguage,
                'quiz_type' => $quizType->value,
                'options_by_question_id' => $optionsByQuestionId,
                'answers' => $answersForGrading,
            ]);

            $this->results = $session->answers->map(fn ($answer): array => [
                'question_id' => (int) $answer->lesson_question_id,
                'prompt' => (string) $answer->prompt,
                'correct_answer' => (string) $answer->correct_answer,
                'selected_answer' => (string) $answer->selected_answer,
                'is_correct' => (bool) $answer->is_correct,
                'match_score' => (int) $answer->match_score,
                'position' => (int) $answer->position,
            ])->all();
            $this->correct_count = (int) $session->correct_count;
            $this->incorrect_count = (int) $session->incorrect_count;
            $this->percentage = (int) $session->percentage;
            $this->saved_session_id = $session->id;
        } else {
            $graded = $quiz->gradeAnswers($answersForGrading, $cardsForGrading, $quizType);
            $this->results = $graded['results'];
            $this->correct_count = $graded['correct_count'];
            $this->incorrect_count = $graded['incorrect_count'];
            $this->percentage = $graded['percentage'];
        }
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Something went wrong grading this quiz. Please try again.';
        $this->showError($message);

        return;
    }

    RateLimiter::hit($throttleKey, 60);

    $this->cards = [];
    $this->quiz_question_ids = [];
    $this->phase = 'results';
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-6">
    <div>
        <a
            href="{{ $course_code ? route('learn.grammar.course', ['course' => $course_code, 'lang' => $translation_language]) : route('learn.grammar') }}"
            wire:navigate
            class="text-sm font-semibold text-[#FF8C42] hover:underline"
        >
            ← Back
        </a>
        <h1 class="mt-3 text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">Grammar quiz</h1>
        <p class="mt-2 text-sm text-[#3D2B1F]/65">5–50 questions · multiple-choice or fill-in-the-blank · no learned/important filtering</p>
    </div>

    @if ($show_error_modal && $error_message)
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-start justify-between gap-3">
            <p>{{ $error_message }}</p>
            <button type="button" wire:click="dismissError" class="font-semibold">Dismiss</button>
        </div>
    @endif

    @if ($phase === 'setup')
        <section id="quiz-setup" class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm space-y-5">
            @if ($lesson_ids === [])
                <p class="text-sm text-[#3D2B1F]/70">
                    No lessons selected.
                    <a href="{{ route('learn.grammar') }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">Choose a course</a>
                    first.
                </p>
            @else
                <p class="text-sm text-[#3D2B1F]/65">{{ count($lesson_ids) }} lesson{{ count($lesson_ids) === 1 ? '' : 's' }} selected</p>
            @endif

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-1">Questions</label>
                <input
                    type="number"
                    min="5"
                    max="50"
                    wire:model="word_count"
                    class="w-full rounded-xl border border-[#3D2B1F]/15 px-3 py-2.5 text-[#3D2B1F] focus:ring-2 focus:ring-[#FF8C42]"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-1">Prompt language</label>
                <select
                    wire:model="translation_language"
                    class="w-full rounded-xl border border-[#3D2B1F]/15 px-3 py-2.5 text-[#3D2B1F] focus:ring-2 focus:ring-[#FF8C42]"
                >
                    @foreach ($languages as $language)
                        <option value="{{ $language['code'] }}">{{ $language['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-2">Quiz type</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="rounded-2xl border px-4 py-3 cursor-pointer {{ $quiz_type === 'multiple_choice' ? 'border-[#FF8C42] bg-[#FFF0E0]' : 'border-[#3D2B1F]/10' }}">
                        <input type="radio" wire:model="quiz_type" value="multiple_choice" class="mr-2 text-[#FF8C42] focus:ring-[#FF8C42]">
                        <span class="font-semibold text-[#3D2B1F]">Multiple choice</span>
                    </label>
                    <label class="rounded-2xl border px-4 py-3 cursor-pointer {{ $quiz_type === 'fill_blank' ? 'border-[#FF8C42] bg-[#FFF0E0]' : 'border-[#3D2B1F]/10' }}">
                        <input type="radio" wire:model="quiz_type" value="fill_blank" class="mr-2 text-[#FF8C42] focus:ring-[#FF8C42]">
                        <span class="font-semibold text-[#3D2B1F]">Fill in the blank</span>
                    </label>
                </div>
            </div>

            <flux:button wire:click="startQuiz" variant="primary" class="w-full !bg-[#FF8C42] hover:!bg-[#f07d33]" :disabled="$lesson_ids === []">
                Start quiz
            </flux:button>
        </section>
    @endif

    @if ($phase === 'quiz')
        @php
            $rtlPrompts = $this->promptsAreRtl();
        @endphp
        <form wire:submit="submitQuiz" class="space-y-4">
            @foreach ($cards as $index => $card)
                @php $qid = (string) $card['question_id']; @endphp
                <div id="quiz-q-{{ $qid }}" class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 shadow-sm space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45" dir="ltr">
                        Question {{ $index + 1 }}
                    </p>
                    <p class="font-semibold text-[#3D2B1F]" dir="{{ $rtlPrompts ? 'rtl' : 'ltr' }}">
                        {{ $card['prompt'] }}
                    </p>

                    @if (($session_quiz_type ?? $quiz_type) === 'multiple_choice')
                        <div class="space-y-2" dir="ltr">
                            @foreach ($card['options'] ?? [] as $option)
                                <label class="flex items-center gap-3 rounded-2xl border border-[#3D2B1F]/10 px-3 py-2.5 cursor-pointer hover:border-[#FF8C42]/40">
                                    <input
                                        type="radio"
                                        wire:model="answers.{{ $qid }}"
                                        value="{{ $option }}"
                                        class="text-[#FF8C42] focus:ring-[#FF8C42]"
                                    >
                                    <span class="text-[#3D2B1F]">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <input
                            type="text"
                            wire:model="answers.{{ $qid }}"
                            autocomplete="off"
                            dir="ltr"
                            class="w-full rounded-xl border border-[#3D2B1F]/15 px-3 py-2.5 text-[#3D2B1F] focus:ring-2 focus:ring-[#FF8C42]"
                            placeholder="Type the answer"
                        >
                    @endif
                </div>
            @endforeach

            <div class="flex gap-3">
                <flux:button type="button" wire:click="backToSetup" variant="ghost" class="flex-1">Cancel</flux:button>
                <flux:button type="submit" variant="primary" class="flex-1 !bg-[#FF8C42] hover:!bg-[#f07d33]">Submit</flux:button>
            </div>
        </form>
    @endif

    @if ($phase === 'results')
        <section class="rounded-3xl bg-[#3D2B1F] text-[#FFF8F0] p-6 shadow-lg space-y-3">
            <p class="text-sm text-white/70">Score</p>
            <p class="text-5xl font-black text-[#FF8C42]">{{ $percentage }}%</p>
            <p class="text-sm">{{ $correct_count }} correct · {{ $incorrect_count }} incorrect</p>
            @guest
                <p class="text-sm text-white/80">
                    <a href="{{ route('login') }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">Sign in</a>
                    to save results and earn XP.
                </p>
            @endguest
            @auth
                @if ($saved_session_id)
                    <livewire:learn.components.quiz-gamification-result
                        activity-type="grammar_quiz"
                        :source-id="$saved_session_id"
                    />
                @endif
            @endauth
        </section>

        @php
            $rtlPrompts = $this->promptsAreRtl();
        @endphp
        <div class="space-y-3">
            @foreach ($results as $row)
                <div class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-4 shadow-sm">
                    <p class="text-sm font-semibold text-[#3D2B1F]" dir="{{ $rtlPrompts ? 'rtl' : 'ltr' }}">
                        {{ $row['prompt'] }}
                    </p>
                    <p class="mt-2 text-sm {{ $row['is_correct'] ? 'text-green-700' : 'text-red-700' }}" dir="ltr">
                        Your answer: {{ $row['selected_answer'] }}
                    </p>
                    @unless ($row['is_correct'])
                        <p class="text-sm text-[#3D2B1F]/70" dir="ltr">Correct: {{ $row['correct_answer'] }}</p>
                    @endunless
                </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <flux:button wire:click="backToSetup" variant="primary" class="flex-1 !bg-[#FF8C42] hover:!bg-[#f07d33]">Try again</flux:button>
            @if ($course_code)
                <a
                    href="{{ route('learn.grammar.course', ['course' => $course_code, 'lang' => $translation_language]) }}"
                    wire:navigate
                    class="inline-flex flex-1 justify-center rounded-xl border border-[#3D2B1F]/15 px-4 py-2.5 text-sm font-bold text-[#3D2B1F]"
                >
                    Back to course
                </a>
            @endif
        </div>
    @endif
</div>
