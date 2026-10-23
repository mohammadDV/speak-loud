<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function view(User $user, Claim $claim): bool
    {
        return $claim->sender_id === $user->id || $claim->receiver_id === $user->id;
    }

    public function accept(User $user, Claim $claim): bool
    {
        return $claim->receiver_id === $user->id && $claim->status === 'pending';
    }

    public function reject(User $user, Claim $claim): bool
    {
        return $claim->receiver_id === $user->id && $claim->status === 'pending';
    }

    public function withdraw(User $user, Claim $claim): bool
    {
        return $claim->sender_id === $user->id && $claim->status === 'pending';
    }
}
