<?php

use App\Domain\Gamification\Actions\ProcessQuizCompletion;
use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Jobs\ProcessQuizCompletionJob;
use App\Domain\Gamification\Listeners\ProcessQuizCompletionListener;
use App\Domain\Gamification\Models\ProcessedEvent;
use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Models\XpTransaction;
use App\Domain\Learning\Events\QuizCompleted;
use App\Domain\Learning\Models\ArticleQuizSession;
use App\Domain\Learning\Models\GrammarQuizSession;
use App\Domain\Learning\Models\MeaningQuizSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 */
function makeArticleSession(User $user, int $correct, int $total, array $overrides = []): ArticleQuizSession
{
    return ArticleQuizSession::factory()->create(array_merge([
        'user_id' => $user->id,
        'word_count' => $total,
        'correct_count' => $correct,
        'incorrect_count' => $total - $correct,
        'percentage' => $total > 0 ? (int) round(($correct / $total) * 100) : 0,
        'completed_at' => now(),
    ], $overrides));
}

test('processing a quiz awards aggregated xp and is idempotent', function () {
    $user = User::factory()->create();
    $session = makeArticleSession($user, 8, 10);

    $event = new QuizCompleted(
        userId: $user->id,
        activityType: 'article_quiz',
        sourceId: (string) $session->id,
        totalQuestions: 10,
        correctAnswers: 8,
        completedAt: CarbonImmutable::now('UTC'),
    );

    $process = app(ProcessQuizCompletion::class);
    $first = $process->execute($event);
    $second = $process->execute($event);

    expect($first)->not->toBeNull()
        ->and($second?->id)->toBe($first?->id)
        ->and(ProcessedEvent::query()->count())->toBe(1);

    $profile = UserGamificationProfile::query()->where('user_id', $user->id)->first();
    expect($profile)->not->toBeNull()
        ->and((int) $profile->total_xp)->toBe(36); // 26 quiz XP + 10 daily goal bonus

    expect(XpTransaction::query()->where('user_id', $user->id)->whereIn('reason', [
        XpReason::CorrectAnswers->value,
        XpReason::QuizCompletion->value,
        XpReason::QuestionMilestone->value,
    ])->count())->toBe(3);
    expect((int) XpTransaction::query()->where('reason', XpReason::CorrectAnswers)->sum('amount'))->toBe(16);
    expect((int) XpTransaction::query()->where('reason', XpReason::QuizCompletion)->sum('amount'))->toBe(5);
    expect((int) XpTransaction::query()->where('reason', XpReason::QuestionMilestone)->sum('amount'))->toBe(5);
});

test('perfect quiz awards perfect bonus', function () {
    $user = User::factory()->create();
    $session = MeaningQuizSession::factory()->create([
        'user_id' => $user->id,
        'word_count' => 10,
        'correct_count' => 10,
        'incorrect_count' => 0,
        'percentage' => 100,
        'completed_at' => now(),
    ]);

    app(ProcessQuizCompletion::class)->execute(new QuizCompleted(
        userId: $user->id,
        activityType: 'meaning_quiz',
        sourceId: (string) $session->id,
        totalQuestions: 10,
        correctAnswers: 10,
        completedAt: CarbonImmutable::now('UTC'),
        mode: 'multiple_choice',
    ));

    expect((int) XpTransaction::query()->where('reason', XpReason::PerfectQuiz)->sum('amount'))->toBe(5)
        ->and((int) UserGamificationProfile::query()->where('user_id', $user->id)->value('total_xp'))->toBe(45); // 35 + goal bonus
});

test('grammar quiz completion awards xp via verifier', function () {
    $user = User::factory()->create();
    $session = GrammarQuizSession::factory()->create([
        'user_id' => $user->id,
        'word_count' => 10,
        'correct_count' => 9,
        'incorrect_count' => 1,
        'percentage' => 90,
        'completed_at' => now(),
    ]);

    app(ProcessQuizCompletion::class)->execute(new QuizCompleted(
        userId: $user->id,
        activityType: 'grammar_quiz',
        sourceId: (string) $session->id,
        totalQuestions: 10,
        correctAnswers: 9,
        completedAt: CarbonImmutable::now('UTC'),
        mode: 'fill_blank',
    ));

    expect(ProcessedEvent::query()->where('activity_type', 'grammar_quiz')->count())->toBe(1)
        ->and((int) UserGamificationProfile::query()->where('user_id', $user->id)->value('total_xp'))->toBeGreaterThan(0);
});

test('below minimum awards only answer xp', function () {
    $user = User::factory()->create();
    $session = makeArticleSession($user, 3, 3);

    app(ProcessQuizCompletion::class)->execute(new QuizCompleted(
        userId: $user->id,
        activityType: 'article_quiz',
        sourceId: (string) $session->id,
        totalQuestions: 3,
        correctAnswers: 3,
        completedAt: CarbonImmutable::now('UTC'),
    ));

    expect((int) UserGamificationProfile::query()->where('user_id', $user->id)->value('total_xp'))->toBe(6)
        ->and(XpTransaction::query()->where('reason', XpReason::QuizCompletion)->count())->toBe(0);
});

test('daily activity cap reduces later rewards', function () {
    config(['gamification.rewards.daily_activity_xp_cap' => 20]);
    $user = User::factory()->create();
    $session = makeArticleSession($user, 10, 10);

    app(ProcessQuizCompletion::class)->execute(new QuizCompleted(
        userId: $user->id,
        activityType: 'article_quiz',
        sourceId: (string) $session->id,
        totalQuestions: 10,
        correctAnswers: 10,
        completedAt: CarbonImmutable::now('UTC'),
    ));

    expect((int) XpTransaction::query()->where('user_id', $user->id)->whereIn('reason', [
        XpReason::CorrectAnswers->value,
        XpReason::QuizCompletion->value,
        XpReason::QuestionMilestone->value,
        XpReason::PerfectQuiz->value,
    ])->sum('amount'))->toBe(20);
});

test('qualifying activity updates streak and can complete daily goal', function () {
    $user = User::factory()->create();
    $session = makeArticleSession($user, 8, 10);

    app(ProcessQuizCompletion::class)->execute(new QuizCompleted(
        userId: $user->id,
        activityType: 'article_quiz',
        sourceId: (string) $session->id,
        totalQuestions: 10,
        correctAnswers: 8,
        completedAt: CarbonImmutable::now('UTC'),
    ));

    $profile = UserGamificationProfile::query()->where('user_id', $user->id)->first();
    expect($profile->current_streak)->toBe(1)
        ->and($profile->total_xp)->toBe(36)
        ->and((int) XpTransaction::query()->where('reason', XpReason::DailyGoalCompleted)->sum('amount'))->toBe(10);
});

test('rejects events that do not match a persisted session owner', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $session = makeArticleSession($owner, 10, 10);

    $result = app(ProcessQuizCompletion::class)->execute(new QuizCompleted(
        userId: $attacker->id,
        activityType: 'article_quiz',
        sourceId: (string) $session->id,
        totalQuestions: 10,
        correctAnswers: 10,
        completedAt: CarbonImmutable::now('UTC'),
    ));

    expect($result)->toBeNull()
        ->and(ProcessedEvent::query()->count())->toBe(0)
        ->and(XpTransaction::query()->count())->toBe(0);
});

test('QuizCompleted listener awards xp synchronously in sync mode', function () {
    config(['gamification.processing.mode' => 'sync']);

    $user = User::factory()->create();
    $session = makeArticleSession($user, 8, 10);

    // Invoke listener directly: RefreshDatabase keeps an open transaction, so
    // ShouldDispatchAfterCommit events would otherwise defer past assertions.
    app(ProcessQuizCompletionListener::class)
        ->handle($session->completionEvent());

    $profile = UserGamificationProfile::query()->where('user_id', $user->id)->first();

    expect(ProcessedEvent::query()->count())->toBe(1)
        ->and($profile)->not->toBeNull()
        ->and((int) $profile->total_xp)->toBeGreaterThan(0)
        ->and(UserDailyActivity::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('QuizCompleted listener dispatches a job in queue mode', function () {
    config(['gamification.processing.mode' => 'queue']);
    Queue::fake();

    $user = User::factory()->create();
    $session = makeArticleSession($user, 8, 10);

    app(ProcessQuizCompletionListener::class)
        ->handle($session->completionEvent());

    Queue::assertPushed(ProcessQuizCompletionJob::class);
    expect(ProcessedEvent::query()->count())->toBe(0);
});
