<?php

namespace App\Domain\Learning\Models;

use App\Models\User;
use Database\Factories\Learning\ArticleQuizSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleQuizSession extends Model
{
    /** @use HasFactory<ArticleQuizSessionFactory> */
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

    protected static function newFactory(): ArticleQuizSessionFactory
    {
        return ArticleQuizSessionFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ArticleQuizAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ArticleQuizAnswer::class)->orderBy('position');
    }
}
