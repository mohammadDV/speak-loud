<?php

namespace App\Domain\Dictionary\Policies;

use App\Domain\Dictionary\Models\Sense;
use App\Models\User;

class SensePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function view(User $user, Sense $sense): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function update(User $user, Sense $sense): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function delete(User $user, Sense $sense): bool
    {
        return in_array($user->role, ['admin', 'reviewer'], true);
    }

    private function isDictionaryStaff(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor', 'reviewer'], true)
            && $user->status === 'active';
    }
}
