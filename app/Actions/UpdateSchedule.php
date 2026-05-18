<?php

namespace App\Actions;

use App\Models\Schedule;
use App\Repositories\Contracts\IScheduleRepository;
use App\Support\ScheduleDayOfWeek;
use RuntimeException;

class UpdateSchedule
{
    public function __construct(private readonly IScheduleRepository $schedules) {}

    public function execute(int $userId, int $scheduleId, array $data): Schedule
    {
        $schedule = $this->schedules->findById($scheduleId);

        if (! $schedule || $schedule->user_id !== $userId) {
            throw new RuntimeException('Schedule not found.');
        }

        if ($data['type'] !== $schedule->type) {
            throw new RuntimeException('Cannot change schedule type.');
        }

        $this->schedules->update($scheduleId, [
            'language_id'      => $data['language_id'],
            'description'      => $data['description'],
            'max_participants' => $data['max_participants'] ?? 1,
        ]);

        if ($schedule->type === 'recurring') {
            $schedule->recurringRule()->updateOrCreate(
                ['schedule_id' => $scheduleId],
                [
                    'day_of_week' => ScheduleDayOfWeek::normalize($data['day_of_week'] ?? ''),
                    'start_time'  => $data['start_time'],
                    'end_time'    => $data['end_time'],
                ]
            );
        } else {
            $schedule->oneTimeSlot()->updateOrCreate(
                ['schedule_id' => $scheduleId],
                [
                    'start_datetime' => $data['start_datetime'],
                    'end_datetime'   => $data['end_datetime'],
                ]
            );
        }

        return $schedule->fresh(['recurringRule', 'oneTimeSlot', 'language']);
    }
}
