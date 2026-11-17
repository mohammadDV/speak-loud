<?php

namespace App\Domain\Gamification\Models;

use App\Models\User;
use Database\Factories\Gamification\UserDailyActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $activity_date
 * @property int $xp_earned
 * @property int $bonus_xp_earned
 * @property int $quizzes_completed
 * @property int $questions_answered
 * @property int $qualifying_activity_count
 * @property int $daily_goal
 * @property Carbon|null $goal_completed_at
 */
class UserDailyActivity extends Model
{
    /** @use HasFactory<UserDailyActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_date',
        'xp_earned',
        'bonus_xp_earned',
        'quizzes_completed',
        'questions_answered',
        'qualifying_activity_count',
        'daily_goal',
        'goal_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'xp_earned' => 'integer',
            'bonus_xp_earned' => 'integer',
            'quizzes_completed' => 'integer',
            'questions_answered' => 'integer',
            'qualifying_activity_count' => 'integer',
            'daily_goal' => 'integer',
            'goal_completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserDailyActivityFactory
    {
        return UserDailyActivityFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
