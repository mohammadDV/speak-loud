<?php

namespace App\Actions;

use App\Repositories\Contracts\IScheduleRepository;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteSchedule
{
    public function __construct(private readonly IScheduleRepository $schedules) {}

    public function execute(int $userId, int $scheduleId): void
    {
        $schedule = $this->schedules->findById($scheduleId);

        if (! $schedule || $schedule->user_id !== $userId) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $this->schedules->delete($scheduleId);
    }
}
