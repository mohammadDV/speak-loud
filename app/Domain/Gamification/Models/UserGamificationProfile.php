<?php

namespace App\Domain\Gamification\Models;

use App\Models\User;
use Database\Factories\Gamification\UserGamificationProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $total_xp
 * @property int $current_streak
 * @property int $longest_streak
 * @property Carbon|null $last_activity_date
 * @property int $daily_goal
 * @property Carbon|null $daily_goal_chosen_at
 * @property string $timezone
 * @property bool $show_on_leaderboard
 * @property string|null $leaderboard_display_name
 */
class UserGamificationProfile extends Model
{
    /** @use HasFactory<UserGamificationProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_xp',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'daily_goal',
        'daily_goal_chosen_at',
        'timezone',
        'show_on_leaderboard',
        'leaderboard_display_name',
    ];

    protected function casts(): array
    {
        return [
            'total_xp' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_activity_date' => 'date',
            'daily_goal' => 'integer',
            'daily_goal_chosen_at' => 'datetime',
            'show_on_leaderboard' => 'boolean',
        ];
    }

    public function needsDailyGoalSetup(): bool
    {
        return $this->daily_goal_chosen_at === null;
    }

    protected static function newFactory(): UserGamificationProfileFactory
    {
        return UserGamificationProfileFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedDisplayName(): string
    {
        if (filled($this->leaderboard_display_name)) {
            return (string) $this->leaderboard_display_name;
        }

        $this->loadMissing('user.profile');
        $profile = $this->user?->profile;

        if ($profile !== null && filled($profile->display_name)) {
            return (string) $profile->display_name;
        }

        if ($profile !== null && filled($profile->username)) {
            return (string) $profile->username;
        }

        return 'Learner';
    }
}
