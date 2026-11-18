<?php

namespace App\Domain\Learning\Models;

use App\Domain\Learning\Enums\LessonContentStatus;
use Database\Factories\Learning\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property LessonContentStatus $status
 */
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'code',
        'position',
        'status',
        'cefr_level',
    ];

    protected function casts(): array
    {
        return [
            'status' => LessonContentStatus::class,
            'position' => 'integer',
        ];
    }

    protected static function newFactory(): LessonFactory
    {
        return LessonFactory::new();
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<LessonTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(LessonTranslation::class);
    }

    /**
     * @return HasMany<LessonQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(LessonQuestion::class);
    }

    /**
     * @return HasMany<UserLessonProgress, $this>
     */
    public function progressRecords(): HasMany
    {
        return $this->hasMany(UserLessonProgress::class);
    }

    public function translationFor(string $languageCode): ?LessonTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('language_code', $languageCode)
                ?? $this->translations->firstWhere('language_code', 'en');
        }

        return $this->translations()
            ->whereIn('language_code', [$languageCode, 'en'])
            ->get()
            ->sortBy(fn (LessonTranslation $translation): int => $translation->language_code === $languageCode ? 0 : 1)
            ->first();
    }

    public function nextLesson(): ?self
    {
        return self::query()
            ->where('course_id', $this->course_id)
            ->where('status', LessonContentStatus::Published)
            ->where('position', '>', $this->position)
            ->orderBy('position')
            ->first();
    }
}
