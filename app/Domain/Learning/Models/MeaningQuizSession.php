<?php

namespace App\Domain\Learning\Models;

use App\Domain\Learning\Enums\MeaningQuizType;
use App\Domain\Learning\Events\QuizCompleted;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Learning\MeaningQuizSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property MeaningQuizType $quiz_type
 */
class MeaningQuizSession extends Model
{
    /** @use HasFactory<MeaningQuizSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level_codes',
        'category_codes',
        'word_count',
        'translation_language_code',
        'quiz_type',
        'correct_count',
        'incorrect_count',
        'percentage',
        'sense_ids',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'level_codes' => 'array',
            'category_codes' => 'array',
            'sense_ids' => 'array',
            'quiz_type' => MeaningQuizType::class,
            'word_count' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'percentage' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): MeaningQuizSessionFactory
    {
        return MeaningQuizSessionFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MeaningQuizAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(MeaningQuizAnswer::class)->orderBy('position');
    }

    public function completionEvent(): QuizCompleted
    {
        if ($this->relationLoaded('answers')) {
            $total = $this->answers->count();
            $correct = $this->answers->where('is_correct', true)->count();
        } else {
            $total = (int) $this->correct_count + (int) $this->incorrect_count;
            $correct = (int) $this->correct_count;
        }

        return new QuizCompleted(
            userId: (int) $this->user_id,
            activityType: 'meaning_quiz',
            sourceId: (string) $this->id,
            totalQuestions: $total,
            correctAnswers: $correct,
            completedAt: CarbonImmutable::parse($this->completed_at)->utc(),
            mode: $this->quiz_type->value,
        );
    }
}
