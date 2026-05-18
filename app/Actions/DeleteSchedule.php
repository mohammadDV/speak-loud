<?php

namespace App\Actions;

use App\Repositories\Contracts\IScheduleRepository;
use RuntimeException;

class DeleteSchedule
{
    public function __construct(private readonly IScheduleRepository $schedules) {}

    public function execute(int $userId, int $scheduleId): void
    {
        $schedule = $this->schedules->findById($scheduleId);

        if (! $schedule || $schedule->user_id !== $userId) {
            throw new RuntimeException('Schedule not found.');
        }

        $this->schedules->delete($scheduleId);
    }
}
