<?php

namespace App\Repositories;

use App\Models\Claim;
use App\Models\Schedule;
use App\Repositories\Contracts\IScheduleRepository;
use Illuminate\Contracts\Pagination\Paginator;
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

    public function countByUser(int $userId): int
    {
        return Schedule::where('user_id', $userId)->count();
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
        return Claim::where('schedule_id', $scheduleId)
            ->where('status', 'accepted')
            ->count();
    }

    public function searchOpenSchedules(array $filters, ?int $viewerId): Paginator
    {
        $with = [
            'user.profile',
            'user.interests',
            'user.languages.language',
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
            ->havingRaw('accepted_claims_count < schedules.max_participants')
            ->where(function ($q) {
                $q->where('schedules.type', 'recurring')
                    ->orWhereHas('oneTimeSlot', fn ($slot) => $slot->where('start_datetime', '>', now('UTC')));
            });

        if ($viewerId !== null) {
            $blockedIds = $this->blockedUserIdsFor($viewerId);

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

        if (! empty($filters['interest_ids'])) {
            $interestIds = array_values(array_filter(array_map('intval', (array) $filters['interest_ids'])));

            if ($interestIds !== []) {
                $query->whereHas('user.interests', fn ($q) => $q->whereIn('interests.id', $interestIds));
            }
        }

        return $query
            ->latest('schedules.created_at')
            ->simplePaginate(20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function openSchedulesForHost(int $hostId, ?int $viewerId): Collection
    {
        $with = [
            'language',
            'user.profile',
            'user.interests',
            'user.languages.language',
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
            ->havingRaw('accepted_claims_count < schedules.max_participants')
            ->where(function ($q) {
                $q->where('type', 'recurring')
                    ->orWhereHas('oneTimeSlot', fn ($slot) => $slot->where('start_datetime', '>', now('UTC')));
            })
            ->latest('created_at')
            ->get();
    }

    /**
     * @return list<int>
     */
    private function blockedUserIdsFor(int $viewerId): array
    {
        $asBlocker = DB::table('user_blocks')
            ->where('blocker_id', $viewerId)
            ->pluck('blocked_id');

        $asBlocked = DB::table('user_blocks')
            ->where('blocked_id', $viewerId)
            ->pluck('blocker_id');

        return $asBlocker
            ->merge($asBlocked)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
