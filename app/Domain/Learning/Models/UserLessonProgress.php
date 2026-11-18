<?php

namespace App\Domain\Learning\Models;

use App\Models\User;
use Database\Factories\Learning\UserLessonProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLessonProgress extends Model
{
    /** @use HasFactory<UserLessonProgressFactory> */
    use HasFactory;

    protected $table = 'user_lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'last_completed_at',
        'best_percentage',
        'attempts_count',
    ];

    protected function casts(): array
    {
        return [
            'last_completed_at' => 'datetime',
            'best_percentage' => 'integer',
            'attempts_count' => 'integer',
        ];
    }

    protected static function newFactory(): UserLessonProgressFactory
    {
        return UserLessonProgressFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
