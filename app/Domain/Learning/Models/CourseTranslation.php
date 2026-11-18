<?php

namespace App\Domain\Learning\Models;

use Database\Factories\Learning\CourseTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseTranslation extends Model
{
    /** @use HasFactory<CourseTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'language_code',
        'title',
        'description',
    ];

    protected static function newFactory(): CourseTranslationFactory
    {
        return CourseTranslationFactory::new();
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
