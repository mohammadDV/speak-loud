<?php

namespace App\Repositories\Contracts;

use App\Models\Claim;
use Illuminate\Support\Collection;

interface IClaimRepository
{
    public function findById(int $id): ?Claim;

    public function findBySenderAndSchedule(int $senderId, int $scheduleId): ?Claim;

    public function create(array $data): Claim;

    public function updateStatus(int $id, string $status): Claim;

    public function incomingForUser(int $userId): Collection;

    public function outgoingForUser(int $userId): Collection;
}
