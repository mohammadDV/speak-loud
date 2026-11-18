<?php

namespace App\Domain\Learning\Models;

use Database\Factories\Learning\GrammarQuizAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrammarQuizAnswer extends Model
{
    /** @use HasFactory<GrammarQuizAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'grammar_quiz_session_id',
        'lesson_question_id',
        'prompt',
        'correct_answer',
        'selected_answer',
        'is_correct',
        'match_score',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'match_score' => 'integer',
            'position' => 'integer',
            'lesson_question_id' => 'integer',
        ];
    }

    protected static function newFactory(): GrammarQuizAnswerFactory
    {
        return GrammarQuizAnswerFactory::new();
    }

    /**
     * @return BelongsTo<GrammarQuizSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GrammarQuizSession::class, 'grammar_quiz_session_id');
    }

    /**
     * @return BelongsTo<LessonQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(LessonQuestion::class, 'lesson_question_id');
    }
}
