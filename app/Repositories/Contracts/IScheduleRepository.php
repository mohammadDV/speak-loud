<?php

namespace App\Repositories\Contracts;

use App\Models\Schedule;
use Illuminate\Support\Collection;

interface IScheduleRepository
{
    public function findById(int $id): ?Schedule;

    public function findByUser(int $userId): Collection;

    public function create(array $data): Schedule;

    public function update(int $id, array $data): Schedule;

    public function delete(int $id): bool;

    public function countAcceptedClaims(int $scheduleId): int;
}
