<?php

namespace App\Actions;

use App\Models\Schedule;
use App\Repositories\Contracts\IScheduleRepository;
use App\Support\ScheduleDayOfWeek;
use App\Support\ScheduleLimits;
use Illuminate\Validation\ValidationException;

class CreateSchedule
{
    public function __construct(
        private readonly IScheduleRepository $schedules,
        private readonly SyncScheduleGroupChat $syncGroupChat,
    ) {}

    public function execute(int $userId, array $data): Schedule
    {
        if ($this->schedules->countByUser($userId) >= ScheduleLimits::maxSlotsForUser()) {
            throw ValidationException::withMessages([
                'title' => ScheduleLimits::limitReachedMessage(),
            ]);
        }

        $schedule = $this->schedules->create([
            'user_id'          => $userId,
            'title'            => trim($data['title']),
            'description'      => $data['description'],
            'type'             => $data['type'],
            'language_id'      => $data['language_id'],
            'max_participants' => $data['max_participants'] ?? 1,
        ]);

        if ($data['type'] === 'recurring') {
            $schedule->recurringRule()->create([
                'day_of_week' => ScheduleDayOfWeek::normalize($data['day_of_week'] ?? ''),
                'start_time'  => $data['start_time'],
                'end_time'    => $data['end_time'],
                'valid_from'  => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
            ]);
        } else {
            $schedule->oneTimeSlot()->create([
                'start_datetime' => $data['start_datetime'],
                'end_datetime'   => $data['end_datetime'],
            ]);
        }

        $schedule->load(['recurringRule', 'oneTimeSlot']);

        $this->syncGroupChat->execute($schedule);

        return $schedule;
    }
}
