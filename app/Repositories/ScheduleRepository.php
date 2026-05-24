<?php

namespace App\Repositories;

use App\Models\Schedule;
use App\Repositories\Contracts\IScheduleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function searchOpenSchedules(array $filters, ?int $viewerId): LengthAwarePaginator
    {
        $with = [
            'user.profile',
            'language',
            'recurringRule',
            'oneTimeSlot',
        ];

        if ($viewerId !== null) {
            $with['claims'] = fn ($q) => $q
                ->where('sender_id', $viewerId)
                ->whereIn('status', ['pending', 'accepted']);
        }

        $query = Schedule::query()
            ->with($with)
            ->withCount([
                'claims as accepted_claims_count' => fn ($q) => $q->where('status', 'accepted'),
            ])
            ->where('schedules.status', 'active')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->whereRaw(
                '(SELECT COUNT(*) FROM claims WHERE claims.schedule_id = schedules.id AND claims.status = ?) < schedules.max_participants',
                ['accepted']
            )
            ->where(function ($q) {
                $q->where('schedules.type', 'recurring')
                    ->orWhereHas('oneTimeSlot', fn ($slot) => $slot->where('start_datetime', '>', now('UTC')));
            });

        if ($viewerId !== null) {
            $blockedIds = DB::table('user_blocks')
                ->where('blocker_id', $viewerId)
                ->orWhere('blocked_id', $viewerId)
                ->pluck(DB::raw("CASE WHEN blocker_id = {$viewerId} THEN blocked_id ELSE blocker_id END"))
                ->all();

            $query
                ->where('schedules.user_id', '!=', $viewerId)
                ->whereNotIn('schedules.user_id', $blockedIds)
                ->whereDoesntHave('claims', fn ($q) => $q
                    ->where('sender_id', $viewerId)
                    ->whereIn('status', ['pending', 'accepted']));
        }

        if (! empty($filters['language_id'])) {
            $query->where('schedules.language_id', $filters['language_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('schedules.type', $filters['type']);
        }

        if (! empty($filters['level'])) {
            $query->whereHas('user.languages', function ($q) use ($filters) {
                $q->whereColumn('user_languages.language_id', 'schedules.language_id')
                    ->where('user_languages.level', $filters['level']);
            });
        }

        if (! empty($filters['country_code'])) {
            $query->whereHas('user.profile', fn ($q) => $q->where('country_code', strtoupper($filters['country_code'])));
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('schedules.description', 'like', $search)
                    ->orWhereHas('user.profile', fn ($profile) => $profile->where('display_name', 'like', $search));
            });
        }

        return $query
            ->latest('schedules.created_at')
            ->paginate(20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function openSchedulesForHost(int $hostId, ?int $viewerId): Collection
    {
        $with = [
            'language',
            'recurringRule',
            'oneTimeSlot',
        ];

        if ($viewerId !== null) {
            $with['claims'] = fn ($q) => $q
                ->where('sender_id', $viewerId)
                ->whereIn('status', ['pending', 'accepted']);
        }

        return Schedule::query()
            ->with($with)
            ->withCount([
                'claims as accepted_claims_count' => fn ($q) => $q->where('status', 'accepted'),
            ])
            ->where('user_id', $hostId)
            ->where('status', 'active')
            ->whereRaw(
                '(SELECT COUNT(*) FROM claims WHERE claims.schedule_id = schedules.id AND claims.status = ?) < schedules.max_participants',
                ['accepted']
            )
            ->where(function ($q) {
                $q->where('type', 'recurring')
                    ->orWhereHas('oneTimeSlot', fn ($slot) => $slot->where('start_datetime', '>', now('UTC')));
            })
            ->latest('created_at')
            ->get();
    }
}
