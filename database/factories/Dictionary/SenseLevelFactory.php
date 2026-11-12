<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SenseLevel>
 */
class SenseLevelFactory extends Factory
{
    protected $model = SenseLevel::class;

    public function definition(): array
    {
        return [
            'sense_id' => Sense::factory(),
            'difficulty_level_id' => DifficultyLevel::query()->value('id') ?? 1,
            'is_primary' => true,
            'confidence' => null,
            'source' => 'editorial',
            'reviewed_by' => null,
            'assessed_at' => now(),
        ];
    }
}
