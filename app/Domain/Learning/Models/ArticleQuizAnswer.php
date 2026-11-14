<?php

namespace App\Domain\Learning\Models;

use App\Domain\Dictionary\Models\Sense;
use Database\Factories\Learning\ArticleQuizAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleQuizAnswer extends Model
{
    /** @use HasFactory<ArticleQuizAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'article_quiz_session_id',
        'sense_id',
        'lemma',
        'correct_article',
        'selected_article',
        'is_correct',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'position' => 'integer',
            'sense_id' => 'integer',
        ];
    }

    protected static function newFactory(): ArticleQuizAnswerFactory
    {
        return ArticleQuizAnswerFactory::new();
    }

    /**
     * @return BelongsTo<ArticleQuizSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ArticleQuizSession::class, 'article_quiz_session_id');
    }

    /**
     * @return BelongsTo<Sense, $this>
     */
    public function sense(): BelongsTo
    {
        return $this->belongsTo(Sense::class);
    }
}
