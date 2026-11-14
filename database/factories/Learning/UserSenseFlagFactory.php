<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\UserSenseFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSenseFlag>
 */
class UserSenseFlagFactory extends Factory
{
    protected $model = UserSenseFlag::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sense_id' => fake()->numberBetween(1, 9999),
            'is_important' => false,
            'is_learned' => false,
        ];
    }

    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_important' => true,
        ]);
    }

    public function learned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_learned' => true,
        ]);
    }
}
