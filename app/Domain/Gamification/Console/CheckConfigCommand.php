<?php

namespace App\Domain\Gamification\Console;

use App\Domain\Gamification\Support\GamificationConfigValidator;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CheckConfigCommand extends Command
{
    protected $signature = 'gamification:check-config';

    protected $description = 'Validate gamification configuration';

    public function handle(GamificationConfigValidator $validator): int
    {
        try {
            $validator->validate();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Gamification configuration is valid.');

        return self::SUCCESS;
    }
}
