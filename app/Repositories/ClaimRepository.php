<?php

namespace App\Repositories;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use Illuminate\Support\Collection;

class ClaimRepository implements IClaimRepository
{
    public function findById(int $id): ?Claim
    {
        return Claim::find($id);
    }

    public function findBySenderAndSchedule(int $senderId, int $scheduleId): ?Claim
    {
        return Claim::where('sender_id', $senderId)
            ->where('schedule_id', $scheduleId)
            ->first();
    }

    public function create(array $data): Claim
    {
        return Claim::create($data);
    }

    public function updateStatus(int $id, string $status): Claim
    {
        $claim = Claim::findOrFail($id);
        $claim->update(['status' => $status]);
        return $claim->fresh();
    }

    public function incomingForUser(int $userId): Collection
    {
        return Claim::whereHas('schedule', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['sender', 'schedule'])->get();
    }

    public function outgoingForUser(int $userId): Collection
    {
        return Claim::where('sender_id', $userId)
            ->with(['schedule', 'schedule.user'])
            ->get();
    }
}
