<?php

namespace App\Domain\Learning\Models;

use App\Domain\Learning\Enums\MeaningQuizType;
use App\Models\User;
use Database\Factories\Learning\MeaningQuizSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
