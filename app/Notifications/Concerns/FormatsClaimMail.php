<?php

namespace App\Notifications\Concerns;

use App\Models\Claim;
use App\Models\User;
use App\Models\UserProfile;

trait FormatsClaimMail
{
    protected function counterpartName(?User $user): string
    {
        if ($user === null) {
            return 'a SpeakLoud user';
        }

        $user->loadMissing('profile');

        $profile = $user->profile;

        if ($profile instanceof UserProfile && filled($profile->display_name)) {
            return $profile->display_name;
        }

        if ($profile instanceof UserProfile && filled($profile->username)) {
            return $profile->username;
        }

        return $user->email ?? 'a SpeakLoud user';
    }

    protected function scheduleContext(Claim $claim): ?string
    {
        $claim->loadMissing('schedule');

        if ($claim->schedule === null) {
            return null;
        }

        $title = $claim->schedule->title;

        if (filled($title)) {
            return $title;
        }

        return $claim->schedule->description;
    }

    protected function claimsUrl(): string
    {
        return route('claims');
    }
}
