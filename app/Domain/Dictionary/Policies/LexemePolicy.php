<?php

namespace App\Domain\Dictionary\Policies;

use App\Domain\Dictionary\Models\Lexeme;
use App\Models\User;

class LexemePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function view(User $user, Lexeme $lexeme): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function update(User $user, Lexeme $lexeme): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function delete(User $user, Lexeme $lexeme): bool
    {
        return in_array($user->role, ['admin', 'reviewer'], true);
    }

    public function submit(User $user, Lexeme $lexeme): bool
    {
        return $this->isDictionaryStaff($user);
    }

    public function review(User $user, Lexeme $lexeme): bool
    {
        return in_array($user->role, ['admin', 'reviewer'], true);
    }

    public function publish(User $user, Lexeme $lexeme): bool
    {
        return in_array($user->role, ['admin', 'reviewer'], true);
    }

    private function isDictionaryStaff(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor', 'reviewer'], true)
            && $user->status === 'active';
    }
}
