<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => SocialAccount::PROVIDER_GOOGLE,
            'provider_id' => (string) fake()->unique()->numerify('##############'),
            'email' => fake()->unique()->safeEmail(),
        ];
    }

    public function google(?string $providerId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => SocialAccount::PROVIDER_GOOGLE,
            'provider_id' => $providerId ?? Str::uuid()->toString(),
        ]);
    }
}
