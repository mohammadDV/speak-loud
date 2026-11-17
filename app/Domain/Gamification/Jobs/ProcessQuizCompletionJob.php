<?php

namespace App\Domain\Gamification\Jobs;

use App\Domain\Gamification\Actions\ProcessQuizCompletion;
use App\Domain\Learning\Events\QuizCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessQuizCompletionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 15, 60];

    public function __construct(
        public QuizCompleted $event,
    ) {
        // Event already implements ShouldDispatchAfterCommit; no second deferral.
    }

    public function handle(ProcessQuizCompletion $processQuizCompletion): void
    {
        $processQuizCompletion->execute($this->event);
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
