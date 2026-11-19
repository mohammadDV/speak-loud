<?php

use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Enums\LessonContentStatus;
use App\Domain\Learning\Models\Course;
use App\Domain\Learning\Models\CourseTranslation;
use App\Domain\Learning\Models\GrammarQuizSession;
use App\Domain\Learning\Models\Lesson;
use App\Domain\Learning\Models\LessonQuestion;
use App\Domain\Learning\Models\LessonQuestionTranslation;
use App\Domain\Learning\Models\LessonTranslation;
use App\Domain\Learning\Models\UserLessonProgress;
use App\Domain\Learning\Services\GrammarQuizService;
use App\Domain\Learning\Services\QuizCompletionVerifier;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Volt\Volt;

/**
 * @return array{course: Course, lesson: Lesson, questions: list<LessonQuestion>}
 */
function createGrammarLessonWithQuestions(int $questionCount = 10): array
{
    $course = Course::factory()->create([
        'code' => 'de-grammar-a1',
        'status' => LessonContentStatus::Published,
        'position' => 1,
    ]);

    CourseTranslation::factory()->create([
        'course_id' => $course->id,
        'language_code' => 'en',
        'title' => 'German Grammar A1/A2',
        'description' => 'Pronouns and present tense',
    ]);

    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'code' => 'pronouns-and-present-tense',
        'status' => LessonContentStatus::Published,
        'position' => 1,
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $lesson->id,
        'language_code' => 'en',
        'title' => 'Pronouns & Present Tense',
        'body_markdown' => "# Pronouns\n\nLearn ich, du, er.",
    ]);

    $questions = [];
    for ($i = 1; $i <= $questionCount; $i++) {
        $answer = 'form'.$i;
        $question = LessonQuestion::factory()->create([
            'lesson_id' => $lesson->id,
            'import_key' => "test:q:{$i}",
            'topic' => 'regular_conjugation',
            'answer' => $answer,
            'distractors' => ['wrong-a'.$i, 'wrong-b'.$i, 'wrong-c'.$i],
            'source_lemma' => 'kommen',
            'status' => LessonContentStatus::Published,
        ]);

        foreach (['en', 'es', 'fa', 'ar', 'tr'] as $lang) {
            LessonQuestionTranslation::factory()->create([
                'lesson_question_id' => $question->id,
                'language_code' => $lang,
                'prompt' => "[{$lang}] Conjugate item {$i}: ___",
            ]);
        }

        $questions[] = $question;
    }

    return compact('course', 'lesson', 'questions');
}

test('guests can view grammar hub and learn index lists grammar lessons', function () {
    createGrammarLessonWithQuestions(5);

    $this->get(route('learn.index'))
        ->assertOk()
        ->assertSee('Grammar lessons')
        ->assertSee('Prepositions')
        ->assertSee(route('learn.prepositions'), false);

    $this->get(route('learn.grammar'))
        ->assertOk()
        ->assertSee('German Grammar A1/A2');
});

test('learn hub features prepositions practice with lesson and quiz links', function () {
    $fixture = createGrammarLessonWithQuestions(5);

    $lesson = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'prepositions-time-and-place',
        'status' => LessonContentStatus::Published,
        'position' => 6,
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $lesson->id,
        'language_code' => 'en',
        'title' => 'Prepositions: Time & Place',
        'body_markdown' => "# Prepositions\n\nWo? uses dative.",
    ]);

    $this->get(route('learn.prepositions'))
        ->assertOk()
        ->assertSee('Prepositions')
        ->assertSee('Practice prepositions')
        ->assertSee('Study the lesson')
        ->assertSee(route('learn.grammar.lesson', [
            'course' => 'de-grammar-a1',
            'lesson' => 'prepositions-time-and-place',
        ]), false)
        ->assertSee('/learn/grammar/quiz?', false)
        ->assertSee('lessons='.$lesson->id, false)
        ->assertSee('course=de-grammar-a1', false);
});

test('grammar course pages render published lessons', function () {
    createGrammarLessonWithQuestions(5);

    $this->get(route('learn.grammar.course', ['course' => 'de-grammar-a1']))
        ->assertOk()
        ->assertSee('Pronouns & Present Tense');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'pronouns-and-present-tense',
    ]))
        ->assertOk()
        ->assertSee('Practice this lesson')
        ->assertSee('lesson-content', false)
        ->assertDontSee('$\\rightarrow$')
        ->assertDontSee('locked', false);

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'pronouns-and-present-tense',
        'lang' => 'fa',
    ]))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('تمرین این درس');
});

test('course lists later lessons and soft-links from lesson one', function () {
    $fixture = createGrammarLessonWithQuestions(5);

    $second = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'modal-verbs',
        'status' => LessonContentStatus::Published,
        'position' => 2,
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $second->id,
        'language_code' => 'en',
        'title' => 'Modal Verbs',
        'body_markdown' => "# Modal Verbs\n\n| Pronoun | können |\n| --- | --- |\n| **ich** | kann |\n",
    ]);

    $third = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'questions-and-word-order',
        'status' => LessonContentStatus::Published,
        'position' => 3,
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $third->id,
        'language_code' => 'en',
        'title' => 'Questions & Word Order',
        'body_markdown' => "# Questions\n\n| W-word | Meaning |\n| --- | --- |\n| **Wo** | where |\n",
    ]);

    $fourth = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'plurals-articles-negation',
        'status' => LessonContentStatus::Published,
        'position' => 4,
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $fourth->id,
        'language_code' => 'en',
        'title' => 'Plurals, Articles & Negation',
        'body_markdown' => "# Plurals\n\n| Singular | Plural |\n| --- | --- |\n| das Kind | die Kinder |\n",
    ]);

    $fifth = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'cases-pronouns-objects',
        'status' => LessonContentStatus::Published,
        'position' => 5,
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $fifth->id,
        'language_code' => 'en',
        'title' => 'Cases, Pronouns & Objects',
        'body_markdown' => "# Cases\n\n| Case | Example |\n| --- | --- |\n| Accusative | den Mann |\n",
    ]);

    $sixth = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'prepositions-time-and-place',
        'status' => LessonContentStatus::Published,
        'position' => 6,
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $sixth->id,
        'language_code' => 'en',
        'title' => 'Prepositions: Time & Place',
        'body_markdown' => "# Prepositions\n\n| Question | Case |\n| --- | --- |\n| Wo? | Dative |\n",
    ]);

    $seventh = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'possessives-welcher-dieser',
        'status' => LessonContentStatus::Published,
        'position' => 7,
        'cefr_level' => 'A2',
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $seventh->id,
        'language_code' => 'en',
        'title' => 'Possessives, welcher & dieser',
        'body_markdown' => "# Possessives\n\n| Stem | Owner |\n| --- | --- |\n| mein- | ich |\n",
    ]);

    $eighth = Lesson::factory()->create([
        'course_id' => $fixture['course']->id,
        'code' => 'dative-verbs-wer-wen-wem',
        'status' => LessonContentStatus::Published,
        'position' => 8,
        'cefr_level' => 'A2',
    ]);

    LessonTranslation::factory()->create([
        'lesson_id' => $eighth->id,
        'language_code' => 'en',
        'title' => 'Dative Verbs & wer/wen/wem',
        'body_markdown' => "# Dative Verbs\n\n| Verb | Case |\n| --- | --- |\n| helfen | Dative |\n",
    ]);

    expect($fixture['lesson']->nextLesson()?->code)->toBe('modal-verbs')
        ->and($second->nextLesson()?->code)->toBe('questions-and-word-order')
        ->and($third->nextLesson()?->code)->toBe('plurals-articles-negation')
        ->and($fourth->nextLesson()?->code)->toBe('cases-pronouns-objects')
        ->and($fifth->nextLesson()?->code)->toBe('prepositions-time-and-place')
        ->and($sixth->nextLesson()?->code)->toBe('possessives-welcher-dieser')
        ->and($seventh->nextLesson()?->code)->toBe('dative-verbs-wer-wen-wem');

    $this->get(route('learn.grammar.course', ['course' => 'de-grammar-a1']))
        ->assertOk()
        ->assertSee('Pronouns & Present Tense')
        ->assertSee('Modal Verbs')
        ->assertSee('Questions & Word Order')
        ->assertSee('Plurals, Articles & Negation')
        ->assertSee('Cases, Pronouns & Objects')
        ->assertSee('Prepositions: Time & Place')
        ->assertSee('Possessives, welcher & dieser')
        ->assertSee('Dative Verbs & wer/wen/wem');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'pronouns-and-present-tense',
    ]))
        ->assertOk()
        ->assertSee('Modal Verbs');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'modal-verbs',
    ]))
        ->assertOk()
        ->assertSee('Modal Verbs')
        ->assertSee('Questions & Word Order')
        ->assertSee('Practice this lesson')
        ->assertSee('kann');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'questions-and-word-order',
    ]))
        ->assertOk()
        ->assertSee('Questions & Word Order')
        ->assertSee('Plurals, Articles & Negation')
        ->assertSee('Practice this lesson')
        ->assertSee('Wo');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'plurals-articles-negation',
    ]))
        ->assertOk()
        ->assertSee('Plurals, Articles & Negation')
        ->assertSee('Cases, Pronouns & Objects')
        ->assertSee('Practice this lesson')
        ->assertSee('Kinder');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'cases-pronouns-objects',
    ]))
        ->assertOk()
        ->assertSee('Cases, Pronouns & Objects')
        ->assertSee('Prepositions: Time & Place')
        ->assertSee('Practice this lesson')
        ->assertSee('den Mann');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'prepositions-time-and-place',
    ]))
        ->assertOk()
        ->assertSee('Prepositions: Time & Place')
        ->assertSee('Possessives, welcher & dieser')
        ->assertSee('Practice this lesson')
        ->assertSee('Wo?');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'possessives-welcher-dieser',
    ]))
        ->assertOk()
        ->assertSee('Possessives, welcher & dieser')
        ->assertSee('Dative Verbs & wer/wen/wem')
        ->assertSee('Practice this lesson')
        ->assertSee('mein-');

    $this->get(route('learn.grammar.lesson', [
        'course' => 'de-grammar-a1',
        'lesson' => 'dative-verbs-wer-wen-wem',
    ]))
        ->assertOk()
        ->assertSee('Dative Verbs & wer/wen/wem')
        ->assertSee('Practice this lesson')
        ->assertSee('helfen');
});

test('guest can complete a grammar quiz without persisting', function () {
    $fixture = createGrammarLessonWithQuestions(8);
    $answersById = [];
    foreach ($fixture['questions'] as $question) {
        $answersById[$question->id] = $question->answer;
    }

    Volt::test('learn.grammar.quiz')
        ->set('lesson_ids', [$fixture['lesson']->id])
        ->set('course_code', 'de-grammar-a1')
        ->set('translation_language', 'en')
        ->set('word_count', 5)
        ->set('quiz_type', GrammarQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->tap(function ($component) use ($answersById) {
            expect($component->get('full_cards') ?? null)->toBeNull();
            $answers = [];
            foreach ($component->get('cards') as $card) {
                expect($card)->toHaveKey('options')
                    ->and($card)->not->toHaveKey('answer');
                expect($card['options'])->toHaveCount(4);
                $answers[(string) $card['question_id']] = $answersById[(int) $card['question_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100);

    expect(GrammarQuizSession::query()->count())->toBe(0);
});

test('grammar quiz does not expose a loadCards action that leaks answers', function () {
    $fixture = createGrammarLessonWithQuestions(8);

    $component = Volt::test('learn.grammar.quiz')
        ->set('lesson_ids', [$fixture['lesson']->id])
        ->set('word_count', 5)
        ->set('quiz_type', GrammarQuizType::MultipleChoice->value);

    expect(fn () => $component->call('loadCards'))->toThrow(MethodNotFoundException::class);
});

test('authenticated user grammar quiz results are persisted and award progress', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
    $fixture = createGrammarLessonWithQuestions(8);
    $answersById = [];
    foreach ($fixture['questions'] as $question) {
        $answersById[$question->id] = $question->answer;
    }

    Volt::actingAs($user)
        ->test('learn.grammar.quiz')
        ->set('lesson_ids', [$fixture['lesson']->id])
        ->set('translation_language', 'en')
        ->set('word_count', 5)
        ->set('quiz_type', GrammarQuizType::FillBlank->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->tap(function ($component) use ($answersById) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                expect($card)->not->toHaveKey('options')
                    ->and($card)->not->toHaveKey('answer');
                $answers[(string) $card['question_id']] = $answersById[(int) $card['question_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100);

    $session = GrammarQuizSession::query()->where('user_id', $user->id)->first();
    expect($session)->not->toBeNull()
        ->and($session->word_count)->toBe(5)
        ->and($session->quiz_type)->toBe(GrammarQuizType::FillBlank)
        ->and($session->answers()->count())->toBe(5);

    $progress = UserLessonProgress::query()
        ->where('user_id', $user->id)
        ->where('lesson_id', $fixture['lesson']->id)
        ->first();

    expect($progress)->not->toBeNull()
        ->and((int) $progress->best_percentage)->toBe(100)
        ->and((int) $progress->attempts_count)->toBe(1);

    $verified = app(QuizCompletionVerifier::class)->verified($session->completionEvent());
    expect($verified->activityType)->toBe('grammar_quiz')
        ->and($verified->mode)->toBe('fill_blank')
        ->and($verified->correctAnswers)->toBe(5);
});

test('grammar quiz prompts render rtl for fa and ar', function () {
    $fixture = createGrammarLessonWithQuestions(5);

    Volt::test('learn.grammar.quiz')
        ->set('lesson_ids', [$fixture['lesson']->id])
        ->set('translation_language', 'fa')
        ->set('word_count', 5)
        ->set('quiz_type', GrammarQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSee('dir="rtl"', false);

    Volt::test('learn.grammar.quiz')
        ->set('lesson_ids', [$fixture['lesson']->id])
        ->set('translation_language', 'ar')
        ->set('word_count', 5)
        ->set('quiz_type', GrammarQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSee('dir="rtl"', false);

    Volt::test('learn.grammar.quiz')
        ->set('lesson_ids', [$fixture['lesson']->id])
        ->set('translation_language', 'en')
        ->set('word_count', 5)
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertDontSee('dir="rtl"', false);
});

test('grammar quiz service rejects invalid word counts and unknown languages', function () {
    $fixture = createGrammarLessonWithQuestions(5);
    $service = app(GrammarQuizService::class);

    expect(fn () => $service->buildQuiz([$fixture['lesson']->id], 3, 'en', GrammarQuizType::MultipleChoice))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->buildQuiz([$fixture['lesson']->id], 5, 'de', GrammarQuizType::MultipleChoice))
        ->toThrow(ValidationException::class);
});

test('inactive users cannot save grammar quiz results', function () {
    $user = User::factory()->create(['status' => 'inactive', 'email_verified_at' => now()]);
    $fixture = createGrammarLessonWithQuestions(5);
    $answersById = [];
    foreach ($fixture['questions'] as $question) {
        $answersById[$question->id] = $question->answer;
    }

    Volt::actingAs($user)
        ->test('learn.grammar.quiz')
        ->set('lesson_ids', [$fixture['lesson']->id])
        ->set('word_count', 5)
        ->call('startQuiz')
        ->tap(function ($component) use ($answersById) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['question_id']] = $answersById[(int) $card['question_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz');

    expect(GrammarQuizSession::query()->count())->toBe(0);
});
