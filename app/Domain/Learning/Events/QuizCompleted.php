<?php

namespace App\Domain\Learning\Events;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use InvalidArgumentException;

final readonly class QuizCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $activityType,
        public string $sourceId,
        public int $totalQuestions,
        public int $correctAnswers,
        public CarbonImmutable $completedAt,
        public ?string $mode = null,
    ) {
        if ($this->totalQuestions < 0) {
            throw new InvalidArgumentException('totalQuestions must be >= 0.');
        }

        if ($this->correctAnswers < 0 || $this->correctAnswers > $this->totalQuestions) {
            throw new InvalidArgumentException('correctAnswers must be between 0 and totalQuestions.');
        }
    }

    public function incorrectAnswers(): int
    {
        return $this->totalQuestions - $this->correctAnswers;
    }

    public function accuracyPercent(): int
    {
        return $this->totalQuestions === 0
            ? 0
            : (int) round($this->correctAnswers / $this->totalQuestions * 100);
    }
}
