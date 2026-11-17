<?php

namespace App\Domain\Learning\Services;

use App\Domain\Learning\Events\QuizCompleted;
use App\Domain\Learning\Models\ArticleQuizSession;
use App\Domain\Learning\Models\MeaningQuizSession;
use InvalidArgumentException;

/**
 * Re-reads persisted quiz sessions so Gamification never trusts event payload alone.
 */
final class QuizCompletionVerifier
{
    public function verified(QuizCompleted $event): QuizCompleted
    {
        // Do not eager-load answers: completionEvent() prefers session aggregates
        // (correct_count / incorrect_count) written by Save*QuizResult.
        $session = match ($event->activityType) {
            'article_quiz' => ArticleQuizSession::query()->find($event->sourceId),
            'meaning_quiz' => MeaningQuizSession::query()->find($event->sourceId),
            default => null,
        };

        if ($session === null) {
            throw new InvalidArgumentException("Quiz session {$event->activityType}:{$event->sourceId} was not found.");
        }

        if ((int) $session->user_id !== $event->userId) {
            throw new InvalidArgumentException('Quiz session does not belong to the event user.');
        }

        if (blank($session->completed_at)) {
            throw new InvalidArgumentException('Quiz session is not completed.');
        }

        $verified = $session->completionEvent();

        if ($verified->userId !== $event->userId
            || $verified->activityType !== $event->activityType
            || $verified->sourceId !== $event->sourceId) {
            throw new InvalidArgumentException('Quiz completion event failed ownership verification.');
        }

        return $verified;
    }
}
