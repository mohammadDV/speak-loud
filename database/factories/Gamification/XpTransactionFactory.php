<?php

namespace Database\Factories\Gamification;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Models\XpTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<XpTransaction>
 */
class XpTransactionFactory extends Factory
{
    protected $model = XpTransaction::class;

    public function definition(): array
    {
        $userId = User::factory();
        $sourceId = (string) fake()->numberBetween(1, 9999);

        return [
            'user_id' => $userId,
            'amount' => fake()->numberBetween(2, 40),
            'reason' => XpReason::CorrectAnswers,
            'source_type' => 'article_quiz',
            'source_id' => $sourceId,
            'idempotency_key' => 'v1:'.Str::uuid()->toString().':article_quiz:'.$sourceId.':correct_answers',
            'local_date' => now()->toDateString(),
            'metadata' => null,
            'created_at' => now(),
        ];
    }
}
