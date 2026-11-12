<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        $code = fake()->unique()->lexify('??');

        return [
            'code' => $code,
            'name_en' => fake()->word(),
            'name_native' => fake()->word(),
            'script' => 'Latin',
            'direction' => 'ltr',
            'is_active' => true,
        ];
    }

    public function german(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'de',
            'name_en' => 'German',
            'name_native' => 'Deutsch',
            'script' => 'Latin',
            'direction' => 'ltr',
        ]);
    }

    public function persian(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'fa',
            'name_en' => 'Persian',
            'name_native' => 'فارسی',
            'script' => 'Arabic',
            'direction' => 'rtl',
        ]);
    }

    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'en',
            'name_en' => 'English',
            'name_native' => 'English',
            'script' => 'Latin',
            'direction' => 'ltr',
        ]);
    }
}
