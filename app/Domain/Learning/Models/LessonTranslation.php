<?php

namespace App\Domain\Learning\Models;

use Database\Factories\Learning\LessonTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonTranslation extends Model
{
    /** @use HasFactory<LessonTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'language_code',
        'title',
        'body_markdown',
    ];

    protected static function newFactory(): LessonTranslationFactory
    {
        return LessonTranslationFactory::new();
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
