<?php

namespace App\Domain\Gamification\Listeners;

use App\Domain\Gamification\Actions\ProcessQuizCompletion;
use App\Domain\Gamification\Jobs\ProcessQuizCompletionJob;
use App\Domain\Learning\Events\QuizCompleted;
use Throwable;

/**
 * Runs sync by default. Queue mode dispatches a dedicated job.
 *
 * Important: do NOT implement ShouldQueue + shouldQueue()=false for "sync".
 * Laravel skips the listener entirely when shouldQueue() returns false.
 */
class ProcessQuizCompletionListener
{
    public function __construct(
        private readonly ProcessQuizCompletion $processQuizCompletion,
    ) {}

    public function handle(QuizCompleted $event): void
    {
        if (config('gamification.processing.mode') === 'queue') {
            ProcessQuizCompletionJob::dispatch($event);

            return;
        }

        try {
            $this->processQuizCompletion->execute($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
