<?php

namespace App\Domain\Learning\Models;

use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Events\QuizCompleted;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Learning\GrammarQuizSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property GrammarQuizType $quiz_type
 * @property list<int> $lesson_ids
 * @property list<int> $question_ids
 */
class GrammarQuizSession extends Model
{
    /** @use HasFactory<GrammarQuizSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_ids',
        'word_count',
        'quiz_type',
        'translation_language_code',
        'correct_count',
        'incorrect_count',
        'percentage',
        'question_ids',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'lesson_ids' => 'array',
            'question_ids' => 'array',
            'quiz_type' => GrammarQuizType::class,
            'word_count' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'percentage' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): GrammarQuizSessionFactory
    {
        return GrammarQuizSessionFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GrammarQuizAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(GrammarQuizAnswer::class)->orderBy('position');
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
            activityType: 'grammar_quiz',
            sourceId: (string) $this->id,
            totalQuestions: $total,
            correctAnswers: $correct,
            completedAt: CarbonImmutable::parse($this->completed_at)->utc(),
            mode: $this->quiz_type->value,
        );
    }
}
