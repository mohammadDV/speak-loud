<?php

namespace App\Domain\Gamification\Queries;

use App\Domain\Gamification\Models\XpTransaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetXpHistory
{
    /**
     * @return LengthAwarePaginator<int, XpTransaction>
     */
    public function execute(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return XpTransaction::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
