<?php

namespace App\Repositories\Contracts;

use App\Models\Claim;
use Illuminate\Support\Collection;

interface IClaimRepository
{
    public function findById(int $id): ?Claim;

    public function findBySenderAndSchedule(int $senderId, int $scheduleId): ?Claim;

    public function findDirectClaimBetweenUsers(int $senderId, int $receiverId): ?Claim;

    public function create(array $data): Claim;

    public function updateStatus(int $id, string $status): Claim;

    public function reopen(int $id, ?string $message): Claim;

    /**
     * @param  array{status?: string, sort?: string}  $filters
     */
    public function incomingForUser(int $userId, array $filters = []): Collection;

    /**
     * @param  array{status?: string, sort?: string}  $filters
     */
    public function outgoingForUser(int $userId, array $filters = []): Collection;
}
