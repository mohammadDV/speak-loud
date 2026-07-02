<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Claims\ClaimResource;
use App\Filament\Admin\Resources\Schedules\ScheduleResource;
use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Claim;
use App\Models\Schedule;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $ttl = Carbon::now()->addMinutes(5);

        return [
            Stat::make('Users', $this->rememberCount('users', $ttl, fn () => User::query()->count()))
                ->icon('heroicon-o-users')
                ->url(UserResource::getUrl('index')),

            Stat::make('Open tickets', $this->rememberCount('tickets_open', $ttl, fn () => Ticket::query()->whereIn('status', ['open', 'in_progress'])->count()))
                ->icon('heroicon-o-lifebuoy')
                ->url(TicketResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => 'open',
                        ],
                    ],
                ])),

            Stat::make('Claims', $this->rememberCount('claims', $ttl, fn () => Claim::query()->count()))
                ->icon('heroicon-o-clipboard-document-list')
                ->url(ClaimResource::getUrl('index')),

            Stat::make('Schedules', $this->rememberCount('schedules', $ttl, fn () => Schedule::query()->count()))
                ->icon('heroicon-o-calendar-days')
                ->url(ScheduleResource::getUrl('index')),
        ];
    }

    /**
     * Cache count queries to avoid repeated DB hits on the dashboard.
     *
     * Cache tags are used when supported (Redis/Memcached), otherwise falls back
     * to regular cache keys (file/database drivers).
     */
    private function rememberCount(string $metric, \DateTimeInterface $ttl, callable $callback): int
    {
        $key = "filament:admin:dashboard:count:{$metric}";

        try {
            return Cache::tags(['filament', 'admin-dashboard'])->remember($key, $ttl, $callback);
        } catch (\Throwable) {
            return Cache::remember($key, $ttl, $callback);
        }
    }
}

