<?php

namespace App\Support;

use App\Models\Faq;
use App\Models\Interest;
use App\Models\Language;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class LookupCache
{
    public const TAG = 'lookups';

    public const KEY_LANGUAGES = 'lookups:languages:active:v2';

    public const KEY_INTERESTS = 'lookups:interests:all:v2';

    public const KEY_FAQS = 'lookups:faqs:active:v2';

    public const KEY_HOME_OPEN_SLOTS = 'lookups:home:open-slots:v2';

    public const KEY_ACTIVITY_LOG_NAMES = 'lookups:activity:log-names:v2';

    /**
     * @return Collection<int, Language>
     */
    public static function activeLanguages(): Collection
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = self::remember(
            self::KEY_LANGUAGES,
            fn () => Language::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get()
                ->map(fn (Language $language) => $language->getAttributes())
                ->all(),
        );

        return self::hydrateModels(Language::class, $rows);
    }

    /**
     * @return Collection<int, Interest>
     */
    public static function interests(): Collection
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = self::remember(
            self::KEY_INTERESTS,
            fn () => Interest::query()
                ->orderBy('name_en')
                ->get()
                ->map(fn (Interest $interest) => $interest->getAttributes())
                ->all(),
        );

        return self::hydrateModels(Interest::class, $rows);
    }

    /**
     * @return Collection<int, Faq>
     */
    public static function activeFaqs(): Collection
    {
        /** @var list<int> $ids */
        $ids = self::remember(
            self::KEY_FAQS,
            fn () => Faq::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        );

        if ($ids === []) {
            return collect();
        }

        return self::orderByIds(
            Faq::query()
                ->with('category')
                ->whereIn('id', $ids)
                ->get(),
            $ids,
        );
    }

    /**
     * @return Collection<int, Schedule>
     */
    public static function homeOpenSlots(int $limit = 4): Collection
    {
        /** @var list<int> $ids */
        $ids = self::remember(
            self::KEY_HOME_OPEN_SLOTS,
            fn () => Schedule::query()
                ->where('status', 'active')
                ->whereHas('user', fn ($q) => $q->where('status', 'active'))
                ->latest()
                ->take($limit)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            freshFor: 60,
            staleFor: 120,
        );

        if ($ids === []) {
            return collect();
        }

        return self::orderByIds(
            Schedule::query()
                ->with(['user.profile', 'language', 'oneTimeSlot', 'recurringRule'])
                ->withCount('claims')
                ->whereIn('id', $ids)
                ->get(),
            $ids,
        );
    }

    /**
     * @return array<string, string>
     */
    public static function activityLogNames(): array
    {
        return self::remember(
            self::KEY_ACTIVITY_LOG_NAMES,
            function (): array {
                return Activity::query()
                    ->whereNotNull('log_name')
                    ->distinct()
                    ->orderBy('log_name')
                    ->limit(100)
                    ->pluck('log_name', 'log_name')
                    ->all();
            },
            freshFor: 300,
            staleFor: 600,
        );
    }

    public static function flush(): void
    {
        try {
            Cache::tags([self::TAG])->flush();
        } catch (Throwable) {
            // no-op; forget individual keys below
        }

        foreach ([
            self::KEY_LANGUAGES,
            self::KEY_INTERESTS,
            self::KEY_FAQS,
            self::KEY_HOME_OPEN_SLOTS,
            self::KEY_ACTIVITY_LOG_NAMES,
            // Legacy keys that stored Eloquent models and can become __PHP_Incomplete_Class.
            'lookups:languages:active',
            'lookups:interests:all',
            'lookups:faqs:active',
            'lookups:home:open-slots',
            'lookups:activity:log-names',
        ] as $key) {
            self::forget($key);
        }
    }

    public static function flushHomeOpenSlots(): void
    {
        self::forget(self::KEY_HOME_OPEN_SLOTS);
        self::forget('lookups:home:open-slots');
    }

    public static function flushActivityLogNames(): void
    {
        self::forget(self::KEY_ACTIVITY_LOG_NAMES);
        self::forget('lookups:activity:log-names');
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function remember(
        string $key,
        callable $callback,
        int $freshFor = 300,
        int $staleFor = 600,
    ): mixed {
        try {
            return Cache::tags([self::TAG])->flexible($key, [$freshFor, $staleFor], $callback);
        } catch (Throwable) {
            return Cache::flexible($key, [$freshFor, $staleFor], $callback);
        }
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  list<array<string, mixed>>|mixed  $rows
     * @return Collection<int, TModel>
     */
    private static function hydrateModels(string $modelClass, mixed $rows): Collection
    {
        if (! is_array($rows)) {
            return collect();
        }

        /** @var TModel $model */
        $model = new $modelClass;

        return $model->newCollection(
            array_map(
                fn (array $attributes) => $model->newFromBuilder($attributes),
                array_values(array_filter($rows, 'is_array')),
            ),
        );
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $models
     * @param  list<int>  $ids
     * @return Collection<int, TModel>
     */
    private static function orderByIds(Collection $models, array $ids): Collection
    {
        $positions = array_flip($ids);

        return $models
            ->sortBy(fn ($model) => $positions[$model->getKey()] ?? PHP_INT_MAX)
            ->values();
    }

    private static function forget(string $key): void
    {
        try {
            Cache::tags([self::TAG])->forget($key);
        } catch (Throwable) {
            // fall through
        }

        Cache::forget($key);
    }
}
