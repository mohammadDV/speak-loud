<?php

namespace App\Domain\Gamification\Services;

use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Models\XpTransaction;
use App\Domain\Gamification\Support\CompetitionPeriod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class LeaderboardService
{
    /**
     * @return array{
     *     period: array{start_utc: string, end_utc: string, start_local: string, timezone: string},
     *     entries: list<array{weekly_xp: int, rank: int, display_name: string}>,
     *     participants: int,
     *     viewer: array{weekly_xp: int, rank: int|null, gap: int|null, next_rank: int|null, hidden: bool}|null
     * }
     */
    public function forPeriod(CompetitionPeriod $period, ?User $viewer = null, int $limit = 10): array
    {
        $cacheKey = 'gamification.leaderboard.'.$period->startLocal->toDateString().'.'.$limit;
        $ttl = $period->endUtc->isFuture()
            ? (int) config('gamification.leaderboard.cache_ttl', 60)
            : (int) config('gamification.leaderboard.historic_cache_ttl', 3600);

        /** @var list<array{user_id: int, weekly_xp: int}> $rows */
        $rows = Cache::remember($cacheKey, $ttl, function () use ($period): array {
            return $this->aggregate($period)->all();
        });

        $ranked = $this->assignRanks($rows);
        $top = array_slice($ranked, 0, $limit);
        $names = $this->displayNames(array_column($top, 'user_id'));

        // Public payloads intentionally omit user_id to avoid leaking internal IDs.
        $entries = array_map(function (array $row) use ($names): array {
            return [
                'weekly_xp' => $row['weekly_xp'],
                'rank' => $row['rank'],
                'display_name' => $names[$row['user_id']] ?? 'Learner',
            ];
        }, $top);

        $viewerData = null;
        if ($viewer !== null) {
            $profile = UserGamificationProfile::query()->where('user_id', $viewer->id)->first();
            $hidden = $profile !== null && ! $profile->show_on_leaderboard;
            $weeklyXp = (int) XpTransaction::query()
                ->where('user_id', $viewer->id)
                ->where('created_at', '>=', $period->startUtc)
                ->where('created_at', '<', $period->endUtc)
                ->sum('amount');

            $rank = null;
            $gap = null;
            $nextRank = null;

            if (! $hidden && $weeklyXp > 0) {
                $rank = 1 + count(array_filter($rows, fn (array $row): bool => $row['weekly_xp'] > $weeklyXp));
                $higher = array_values(array_filter($rows, fn (array $row): bool => $row['weekly_xp'] > $weeklyXp));
                if ($higher !== []) {
                    $nextScore = min(array_column($higher, 'weekly_xp'));
                    $gap = $nextScore - $weeklyXp;
                    $nextRank = 1 + count(array_filter($rows, fn (array $row): bool => $row['weekly_xp'] > $nextScore));
                }
            }

            $viewerData = [
                'weekly_xp' => $weeklyXp,
                'rank' => $rank,
                'gap' => $gap,
                'next_rank' => $nextRank,
                'hidden' => $hidden || ($profile === null && $weeklyXp <= 0),
            ];
        }

        return [
            'period' => $period->toArray(),
            'entries' => $entries,
            'participants' => count($rows),
            'viewer' => $viewerData,
        ];
    }

    /**
     * @return Collection<int, array{user_id: int, weekly_xp: int}>
     */
    private function aggregate(CompetitionPeriod $period): Collection
    {
        /** @var list<array{user_id: int, weekly_xp: int}> $rows */
        $rows = DB::table('xp_transactions as t')
            ->join('user_gamification_profiles as p', 'p.user_id', '=', 't.user_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('t.created_at', '>=', $period->startUtc)
            ->where('t.created_at', '<', $period->endUtc)
            ->where('p.show_on_leaderboard', true)
            ->whereNull('u.deleted_at')
            ->groupBy('t.user_id')
            ->havingRaw('SUM(t.amount) > 0')
            ->orderByDesc(DB::raw('SUM(t.amount)'))
            ->orderBy('t.user_id')
            ->get([
                't.user_id',
                DB::raw('SUM(t.amount) as weekly_xp'),
            ])
            ->map(static fn ($row): array => [
                'user_id' => (int) $row->user_id,
                'weekly_xp' => (int) $row->weekly_xp,
            ])
            ->values()
            ->all();

        return collect($rows);
    }

    /**
     * @param  list<array{user_id: int, weekly_xp: int}>  $rows
     * @return list<array{user_id: int, weekly_xp: int, rank: int}>
     */
    private function assignRanks(array $rows): array
    {
        $ranked = [];
        $position = 0;
        $lastXp = null;
        $rank = 0;

        foreach ($rows as $row) {
            $position++;
            if ($lastXp === null || $row['weekly_xp'] !== $lastXp) {
                $rank = $position;
                $lastXp = $row['weekly_xp'];
            }
            $ranked[] = [
                'user_id' => $row['user_id'],
                'weekly_xp' => $row['weekly_xp'],
                'rank' => $rank,
            ];
        }

        return $ranked;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, string>
     */
    private function displayNames(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $profiles = UserGamificationProfile::query()
            ->with('user.profile')
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $names = [];
        foreach ($userIds as $userId) {
            /** @var UserGamificationProfile|null $profile */
            $profile = $profiles->get($userId);
            $names[$userId] = $profile?->resolvedDisplayName() ?? 'Learner';
        }

        return $names;
    }
}
