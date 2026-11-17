<?php

namespace App\Domain\Gamification\Services;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Support\ActivityRegistry;

final class XpCalculationService
{
    public function __construct(
        private readonly ActivityRegistry $registry,
    ) {}

    /**
     * Resolve a scoring amount for an activity/mode/reason using config overrides.
     */
    public function resolveAmount(string $activityType, XpReason $reason, ?string $mode = null): int
    {
        $key = $reason->configKey();
        $activity = $this->registry->get($activityType);

        if ($activity !== null && $mode !== null) {
            $modeXp = $activity['modes'][$mode]['xp'][$key] ?? null;
            if (is_int($modeXp)) {
                return $modeXp;
            }
        }

        if ($activity !== null) {
            $activityXp = $activity['xp'][$key] ?? null;
            if (is_int($activityXp)) {
                return $activityXp;
            }
        }

        return (int) config("gamification.xp.{$key}", 0);
    }

    /**
     * @return list<array{reason: XpReason, amount: int, capped: bool}>
     */
    public function calculateQuizRewards(
        string $activityType,
        int $totalQuestions,
        int $correctAnswers,
        int $remainingDailyCap,
        ?string $mode = null,
    ): array {
        $minimum = (int) config('gamification.rewards.minimum_questions', 5);
        $milestoneSize = (int) config('gamification.rewards.question_milestone_size', 10);
        $meetsMinimum = $totalQuestions >= $minimum;

        $planned = [];

        if ($correctAnswers > 0) {
            $perAnswer = $this->resolveAmount($activityType, XpReason::CorrectAnswers, $mode);
            $planned[] = [
                'reason' => XpReason::CorrectAnswers,
                'amount' => $correctAnswers * $perAnswer,
            ];
        }

        if ($meetsMinimum) {
            $planned[] = [
                'reason' => XpReason::QuizCompletion,
                'amount' => $this->resolveAmount($activityType, XpReason::QuizCompletion, $mode),
            ];

            $milestones = intdiv($totalQuestions, $milestoneSize);
            if ($milestones > 0) {
                $planned[] = [
                    'reason' => XpReason::QuestionMilestone,
                    'amount' => $milestones * $this->resolveAmount($activityType, XpReason::QuestionMilestone, $mode),
                ];
            }

            if ($correctAnswers === $totalQuestions && $totalQuestions > 0) {
                $planned[] = [
                    'reason' => XpReason::PerfectQuiz,
                    'amount' => $this->resolveAmount($activityType, XpReason::PerfectQuiz, $mode),
                ];
            }
        }

        $remaining = max(0, $remainingDailyCap);
        $rewards = [];

        foreach ($planned as $item) {
            $amount = (int) $item['amount'];
            if ($amount <= 0) {
                continue;
            }

            $capped = false;
            if ($remaining <= 0) {
                continue;
            }

            if ($amount > $remaining) {
                $amount = $remaining;
                $capped = true;
            }

            $remaining -= $amount;
            $rewards[] = [
                'reason' => $item['reason'],
                'amount' => $amount,
                'capped' => $capped || ($item['amount'] > $amount),
            ];
        }

        return $rewards;
    }
}
