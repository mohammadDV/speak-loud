<?php

namespace App\Domain\Gamification\Console;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Models\XpTransaction;
use App\Domain\Gamification\Services\StreakService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileGamificationCommand extends Command
{
    protected $signature = 'gamification:reconcile {--days=14 : Recent daily rows to reconcile}';

    protected $description = 'Reconcile gamification aggregates from the XP ledger';

    public function handle(StreakService $streaks): int
    {
        $days = max(1, (int) $this->option('days'));
        $since = now()->subDays($days)->toDateString();
        $fixed = 0;

        UserGamificationProfile::query()->orderBy('id')->each(function (UserGamificationProfile $profile) use ($streaks, $since, &$fixed): void {
            $ledgerTotal = (int) XpTransaction::query()->where('user_id', $profile->user_id)->sum('amount');
            if ($profile->total_xp !== $ledgerTotal) {
                Log::warning('gamification.reconcile.total_xp', [
                    'user_id' => $profile->user_id,
                    'before' => $profile->total_xp,
                    'after' => $ledgerTotal,
                ]);
                $profile->total_xp = $ledgerTotal;
                $fixed++;
            }

            UserDailyActivity::query()
                ->where('user_id', $profile->user_id)
                ->whereDate('activity_date', '>=', $since)
                ->each(function (UserDailyActivity $daily) use ($profile, &$fixed): void {
                    $activityXp = (int) XpTransaction::query()
                        ->where('user_id', $profile->user_id)
                        ->whereDate('local_date', $daily->activity_date->toDateString())
                        ->whereIn('reason', array_map(
                            static fn (XpReason $reason): string => $reason->value,
                            array_filter(XpReason::cases(), static fn (XpReason $reason): bool => $reason->isActivityXp()),
                        ))
                        ->sum('amount');

                    $bonusXp = (int) XpTransaction::query()
                        ->where('user_id', $profile->user_id)
                        ->whereDate('local_date', $daily->activity_date->toDateString())
                        ->whereIn('reason', [
                            XpReason::DailyGoalCompleted->value,
                            XpReason::AchievementUnlocked->value,
                        ])
                        ->sum('amount');

                    if ($daily->xp_earned !== $activityXp || $daily->bonus_xp_earned !== $bonusXp) {
                        Log::warning('gamification.reconcile.daily', [
                            'user_id' => $profile->user_id,
                            'date' => $daily->activity_date->toDateString(),
                            'xp_before' => $daily->xp_earned,
                            'xp_after' => $activityXp,
                        ]);
                        $daily->xp_earned = $activityXp;
                        $daily->bonus_xp_earned = $bonusXp;
                        $daily->save();
                        $fixed++;
                    }
                });

            if ($profile->last_activity_date !== null) {
                $recalculated = $streaks->recalculateFromDailyRows(
                    $profile->user_id,
                    $profile->last_activity_date->toDateString(),
                );
                if ($profile->current_streak !== $recalculated) {
                    Log::warning('gamification.reconcile.streak', [
                        'user_id' => $profile->user_id,
                        'before' => $profile->current_streak,
                        'after' => $recalculated,
                    ]);
                    $profile->current_streak = $recalculated;
                    $profile->longest_streak = max($profile->longest_streak, $recalculated);
                    $fixed++;
                }
            }

            $profile->save();
        });

        $this->info("Reconciliation complete. Corrections applied: {$fixed}");

        return self::SUCCESS;
    }
}
