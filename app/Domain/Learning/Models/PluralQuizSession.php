<?php

namespace App\Domain\Learning\Models;

use App\Domain\Learning\Events\QuizCompleted;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Learning\PluralQuizSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PluralQuizSession extends Model
{
    /** @use HasFactory<PluralQuizSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level_codes',
        'category_codes',
        'word_count',
        'translation_language_code',
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
            'word_count' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'percentage' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): PluralQuizSessionFactory
    {
        return PluralQuizSessionFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PluralQuizAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(PluralQuizAnswer::class)->orderBy('position');
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
            activityType: 'plural_quiz',
            sourceId: (string) $this->id,
            totalQuestions: $total,
            correctAnswers: $correct,
            completedAt: CarbonImmutable::parse($this->completed_at)->utc(),
            mode: null,
        );
    }
}
