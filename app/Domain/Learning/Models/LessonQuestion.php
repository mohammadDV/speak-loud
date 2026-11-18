<?php

namespace App\Domain\Learning\Models;

use App\Domain\Learning\Enums\LessonContentStatus;
use Database\Factories\Learning\LessonQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property LessonContentStatus $status
 * @property list<string> $distractors
 * @property array<string, mixed>|null $meta
 */
class LessonQuestion extends Model
{
    /** @use HasFactory<LessonQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'import_key',
        'topic',
        'answer',
        'distractors',
        'blank_token',
        'source_lemma',
        'sense_import_key',
        'meta',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'distractors' => 'array',
            'meta' => 'array',
            'status' => LessonContentStatus::class,
        ];
    }

    protected static function newFactory(): LessonQuestionFactory
    {
        return LessonQuestionFactory::new();
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return HasMany<LessonQuestionTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(LessonQuestionTranslation::class);
    }

    public function translationFor(string $languageCode): ?LessonQuestionTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('language_code', $languageCode)
                ?? $this->translations->firstWhere('language_code', 'en');
        }

        return $this->translations()
            ->whereIn('language_code', [$languageCode, 'en'])
            ->get()
            ->sortBy(fn (LessonQuestionTranslation $translation): int => $translation->language_code === $languageCode ? 0 : 1)
            ->first();
    }
}
