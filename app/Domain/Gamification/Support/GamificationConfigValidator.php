<?php

namespace App\Domain\Gamification\Support;

use InvalidArgumentException;

final class GamificationConfigValidator
{
    public function validate(): void
    {
        $errors = [];

        foreach (['correct_answer', 'quiz_completion', 'question_milestone', 'perfect_quiz', 'daily_goal_completed'] as $key) {
            $value = config("gamification.xp.{$key}");
            if (! is_int($value) || $value < 0) {
                $errors[] = "gamification.xp.{$key} must be a non-negative integer.";
            }
        }

        $min = config('gamification.daily_goal.minimum');
        $default = config('gamification.daily_goal.default');
        $max = config('gamification.daily_goal.maximum');

        if (! is_int($min) || ! is_int($default) || ! is_int($max) || $min > $default || $default > $max) {
            $errors[] = 'gamification.daily_goal requires minimum ≤ default ≤ maximum.';
        }

        /** @var mixed $presets */
        $presets = config('gamification.daily_goal.presets', []);
        if (! is_array($presets)) {
            $errors[] = 'gamification.daily_goal.presets must be an array.';
        } else {
            foreach ($presets as $name => $value) {
                if (! is_int($value) || $value < (int) $min || $value > (int) $max) {
                    $errors[] = "gamification.daily_goal.presets.{$name} must be within min/max.";
                }
            }
        }

        $minimumQuestions = config('gamification.rewards.minimum_questions');
        if (! is_int($minimumQuestions) || $minimumQuestions < 1) {
            $errors[] = 'gamification.rewards.minimum_questions must be an integer ≥ 1.';
        }

        $cap = config('gamification.rewards.daily_activity_xp_cap');
        if (! is_int($cap) || $cap < 0) {
            $errors[] = 'gamification.rewards.daily_activity_xp_cap must be an integer ≥ 0.';
        }

        $streakMin = config('gamification.streak.minimum_questions');
        if (! is_int($streakMin) || $streakMin < 1) {
            $errors[] = 'gamification.streak.minimum_questions must be an integer ≥ 1.';
        }

        $shortQuiz = config('gamification.recommendations.short_quiz_questions');
        if (! is_int($shortQuiz) || $shortQuiz < (int) $streakMin) {
            $errors[] = 'gamification.recommendations.short_quiz_questions must be ≥ streak.minimum_questions.';
        }

        foreach (['competition_timezone', 'default_user_timezone'] as $tzKey) {
            $tz = config("gamification.{$tzKey}");
            if (! is_string($tz) || $tz === '' || ! in_array($tz, timezone_identifiers_list(), true)) {
                $errors[] = "gamification.{$tzKey} must be a valid IANA timezone.";
            }
        }

        $mode = config('gamification.processing.mode');
        if (! in_array($mode, ['sync', 'queue'], true)) {
            $errors[] = 'gamification.processing.mode must be sync or queue.';
        }

        /** @var array<string, array<string, mixed>> $activities */
        $activities = config('gamification.activities', []);
        foreach ($activities as $type => $activity) {
            foreach (['label', 'url', 'flag_context'] as $required) {
                if (! is_string($activity[$required] ?? null) || $activity[$required] === '') {
                    $errors[] = "gamification.activities.{$type}.{$required} is required.";
                }
            }
            if (! is_int($activity['seconds_per_question'] ?? null) || $activity['seconds_per_question'] <= 0) {
                $errors[] = "gamification.activities.{$type}.seconds_per_question must be > 0.";
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }
    }
}
