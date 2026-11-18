<?php

namespace App\Domain\Learning\Models;

use App\Domain\Learning\Enums\LessonContentStatus;
use Database\Factories\Learning\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property LessonContentStatus $status
 */
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'cefr_level',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'status' => LessonContentStatus::class,
            'position' => 'integer',
        ];
    }

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }

    /**
     * @return HasMany<CourseTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CourseTranslation::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function translationFor(string $languageCode): ?CourseTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('language_code', $languageCode)
                ?? $this->translations->firstWhere('language_code', 'en');
        }

        return $this->translations()
            ->whereIn('language_code', [$languageCode, 'en'])
            ->get()
            ->sortBy(fn (CourseTranslation $translation): int => $translation->language_code === $languageCode ? 0 : 1)
            ->first();
    }
}
