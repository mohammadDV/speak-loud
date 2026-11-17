<?php

namespace App\Domain\Gamification\Enums;

enum XpReason: string
{
    case CorrectAnswers = 'correct_answers';
    case QuizCompletion = 'quiz_completion';
    case QuestionMilestone = 'question_milestone';
    case PerfectQuiz = 'perfect_quiz';
    case DailyGoalCompleted = 'daily_goal_completed';
    case AchievementUnlocked = 'achievement_unlocked';
    case AdministrativeAdjustment = 'administrative_adjustment';

    /**
     * Counts toward daily goal progress and the daily activity cap.
     */
    public function isActivityXp(): bool
    {
        return in_array($this, [
            self::CorrectAnswers,
            self::QuizCompletion,
            self::QuestionMilestone,
            self::PerfectQuiz,
        ], true);
    }

    /**
     * Config key used to resolve the XP amount for this reason.
     */
    public function configKey(): string
    {
        return match ($this) {
            self::CorrectAnswers => 'correct_answer',
            self::QuizCompletion => 'quiz_completion',
            self::QuestionMilestone => 'question_milestone',
            self::PerfectQuiz => 'perfect_quiz',
            self::DailyGoalCompleted => 'daily_goal_completed',
            self::AchievementUnlocked => 'achievement_unlocked',
            self::AdministrativeAdjustment => 'administrative_adjustment',
        };
    }
}
