<?php

namespace App\Repositories;

use App\Models\Schedule;
use App\Repositories\Contracts\IScheduleRepository;
use Illuminate\Support\Collection;

class ScheduleRepository implements IScheduleRepository
{
    public function findById(int $id): ?Schedule
    {
        return Schedule::find($id);
    }

    public function findByUser(int $userId): Collection
    {
        return Schedule::where('user_id', $userId)->get();
    }

    public function create(array $data): Schedule
    {
        return Schedule::create($data);
    }

    public function update(int $id, array $data): Schedule
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->update($data);
        return $schedule->fresh();
    }

    public function delete(int $id): bool
    {
        return Schedule::findOrFail($id)->delete();
    }

    public function countAcceptedClaims(int $scheduleId): int
    {
        return \App\Models\Claim::where('schedule_id', $scheduleId)
            ->where('status', 'accepted')
            ->count();
    }
}
