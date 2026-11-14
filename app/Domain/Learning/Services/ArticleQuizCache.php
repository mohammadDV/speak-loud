<?php

namespace App\Domain\Learning\Services;

use Illuminate\Support\Facades\Cache;

class ArticleQuizCache
{
    private const POOL_VERSION_KEY = 'learn:article-pool-version';

    private const POOL_KEY = 'learn:article-pool:v%d:%s:%s';

    private const CARD_KEY = 'learn:article-card:%d:%s';

    private const CARD_TARGETS_KEY = 'learn:article-card:%d:targets';

    private const TRANSLATION_LANGUAGES_KEY = 'learn:article-translation-languages:v%d';

    public function poolVersion(): int
    {
        return (int) Cache::get(self::POOL_VERSION_KEY, 1);
    }

    /**
     * @param  callable(): list<array{id: int, code: string, name_en: string, name_native: string}>  $callback
     * @return list<array{id: int, code: string, name_en: string, name_native: string}>
     */
    public function rememberTranslationLanguages(callable $callback): array
    {
        $key = sprintf(self::TRANSLATION_LANGUAGES_KEY, $this->poolVersion());

        /** @var list<array{id: int, code: string, name_en: string, name_native: string}> */
        return Cache::remember($key, now()->addHour(), $callback);
    }

    /**
     * @param  list<string>  $levelCodes
     * @param  list<string>  $categoryCodes
     * @param  callable(): list<array{sense_id: int, article: string, lemma: string}>  $callback
     * @return list<array{sense_id: int, article: string, lemma: string}>
     */
    public function rememberPool(array $levelCodes, array $categoryCodes, callable $callback): array
    {
        $key = sprintf(
            self::POOL_KEY,
            $this->poolVersion(),
            $this->normalizeFilterList($levelCodes),
            $this->normalizeFilterList($categoryCodes),
        );

        /** @var list<array{sense_id: int, article: string, lemma: string}> */
        return Cache::remember($key, now()->addHour(), $callback);
    }

    public function forgetPools(): void
    {
        $version = $this->poolVersion();
        Cache::put(self::POOL_VERSION_KEY, $version + 1, now()->addDays(30));
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public function rememberCard(int $senseId, string $languageCode, callable $callback): array
    {
        $lang = $this->normalizeFilter($languageCode);
        $key = sprintf(self::CARD_KEY, $senseId, $lang);

        /** @var array<string, mixed> $card */
        $card = Cache::remember($key, now()->addMinutes(30), $callback);
        $this->rememberCardTarget($senseId, $lang);

        return $card;
    }

    /**
     * @param  list<int>  $senseIds
     */
    public function forgetCardsForSenses(array $senseIds): void
    {
        foreach ($senseIds as $senseId) {
            $targetsKey = sprintf(self::CARD_TARGETS_KEY, $senseId);
            /** @var list<string> $targets */
            $targets = Cache::get($targetsKey, ['en', 'all']);

            foreach (array_unique($targets) as $target) {
                Cache::forget(sprintf(self::CARD_KEY, $senseId, $target));
            }

            Cache::forget($targetsKey);
        }
    }

    private function normalizeFilter(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'all';
        }

        return strtolower(trim($value));
    }

    /**
     * @param  list<string>  $values
     */
    private function normalizeFilterList(array $values): string
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => strtolower(trim((string) $value)),
            $values,
        ), static fn (string $value): bool => $value !== '')));

        sort($normalized);

        return $normalized === [] ? 'all' : implode(',', $normalized);
    }

    private function rememberCardTarget(int $senseId, string $lang): void
    {
        $targetsKey = sprintf(self::CARD_TARGETS_KEY, $senseId);
        /** @var list<string> $targets */
        $targets = Cache::get($targetsKey, []);
        $targets[] = $lang;
        Cache::put($targetsKey, array_values(array_unique($targets)), now()->addMinutes(30));
    }
}
