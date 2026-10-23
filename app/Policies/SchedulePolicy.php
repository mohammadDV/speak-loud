<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function view(User $user, Schedule $schedule): bool
    {
        return $schedule->userCanView($user->id);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $schedule->isHost($user->id);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $schedule->isHost($user->id);
    }

    public function seeParticipants(User $user, Schedule $schedule): bool
    {
        return $schedule->userCanSeeParticipants($user->id);
    }

    public function accessGroupChat(User $user, Schedule $schedule): bool
    {
        return $schedule->userCanAccessGroupChat($user->id);
    }
}
