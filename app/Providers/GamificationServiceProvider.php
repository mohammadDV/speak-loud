<?php

namespace App\Providers;

use App\Domain\Gamification\Console\CheckConfigCommand;
use App\Domain\Gamification\Console\ReconcileGamificationCommand;
use App\Domain\Gamification\Listeners\ProcessQuizCompletionListener;
use App\Domain\Gamification\Support\GamificationConfigValidator;
use App\Domain\Learning\Console\RedispatchQuizCompletionsCommand;
use App\Domain\Learning\Events\QuizCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class GamificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(QuizCompleted::class, ProcessQuizCompletionListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckConfigCommand::class,
                ReconcileGamificationCommand::class,
                RedispatchQuizCompletionsCommand::class,
            ]);
        }

        if ($this->app->environment(['local', 'testing'])) {
            try {
                $this->app->make(GamificationConfigValidator::class)->validate();
            } catch (\InvalidArgumentException) {
                // Invalid config should fail loudly in tests when commands/tests assert it;
                // boot must not break the whole app if config is mid-edit.
            }
        }
    }
}
