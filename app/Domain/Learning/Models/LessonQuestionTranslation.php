<?php

namespace App\Domain\Learning\Models;

use Database\Factories\Learning\LessonQuestionTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonQuestionTranslation extends Model
{
    /** @use HasFactory<LessonQuestionTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'lesson_question_id',
        'language_code',
        'prompt',
        'hint',
        'explanation',
    ];

    protected static function newFactory(): LessonQuestionTranslationFactory
    {
        return LessonQuestionTranslationFactory::new();
    }

    /**
     * @return BelongsTo<LessonQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(LessonQuestion::class, 'lesson_question_id');
    }
}
