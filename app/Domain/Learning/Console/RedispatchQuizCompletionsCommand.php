<?php

namespace App\Domain\Learning\Console;

use App\Domain\Learning\Models\ArticleQuizSession;
use App\Domain\Learning\Models\MeaningQuizSession;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RedispatchQuizCompletionsCommand extends Command
{
    protected $signature = 'learning:redispatch-completions
        {--since= : Completed sessions on or after this date (required, Y-m-d)}
        {--activity= : Optional activity type filter (article_quiz|meaning_quiz)}';

    protected $description = 'Re-dispatch QuizCompleted events for completed sessions (idempotent recovery)';

    public function handle(): int
    {
        $since = $this->option('since');
        if (! is_string($since) || $since === '') {
            $this->error('The --since option is required (Y-m-d).');

            return self::FAILURE;
        }

        try {
            $from = CarbonImmutable::parse($since)->startOfDay();
        } catch (\Throwable) {
            $this->error('Invalid --since date.');

            return self::FAILURE;
        }

        $activity = $this->option('activity');
        $dispatched = 0;

        if ($activity === null || $activity === 'article_quiz') {
            ArticleQuizSession::query()
                ->where('completed_at', '>=', $from)
                ->orderBy('id')
                ->each(function (ArticleQuizSession $session) use (&$dispatched): void {
                    event($session->completionEvent());
                    $dispatched++;
                });
        }

        if ($activity === null || $activity === 'meaning_quiz') {
            MeaningQuizSession::query()
                ->where('completed_at', '>=', $from)
                ->orderBy('id')
                ->each(function (MeaningQuizSession $session) use (&$dispatched): void {
                    event($session->completionEvent());
                    $dispatched++;
                });
        }

        $this->info("Dispatched {$dispatched} QuizCompleted event(s).");

        return self::SUCCESS;
    }
}
