<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Services\GamificationProfileService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateGamificationSettings
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
    ) {}

    /**
     * @param  array{timezone?: string, show_on_leaderboard?: bool, leaderboard_display_name?: string|null}  $data
     */
    public function execute(User $user, array $data): void
    {
        if (array_key_exists('timezone', $data)) {
            $timezone = (string) $data['timezone'];
            if ($timezone === '' || ! in_array($timezone, timezone_identifiers_list(), true)) {
                throw ValidationException::withMessages([
                    'timezone' => 'Choose a valid timezone.',
                ]);
            }
        }

        if (array_key_exists('leaderboard_display_name', $data) && $data['leaderboard_display_name'] !== null) {
            $name = trim((string) $data['leaderboard_display_name']);
            if (mb_strlen($name) > 40) {
                throw ValidationException::withMessages([
                    'leaderboard_display_name' => 'Display name may be at most 40 characters.',
                ]);
            }
            $data['leaderboard_display_name'] = $name === '' ? null : $name;
        }

        DB::transaction(function () use ($user, $data): void {
            $profile = $this->profiles->lockForUser($user->id);

            if (array_key_exists('timezone', $data)) {
                $profile->timezone = (string) $data['timezone'];
            }
            if (array_key_exists('show_on_leaderboard', $data)) {
                $profile->show_on_leaderboard = (bool) $data['show_on_leaderboard'];
            }
            if (array_key_exists('leaderboard_display_name', $data)) {
                $profile->leaderboard_display_name = $data['leaderboard_display_name'];
            }

            $profile->save();
        });
    }
}
