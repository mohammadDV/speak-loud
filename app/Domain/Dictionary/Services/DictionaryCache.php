<?php

namespace App\Domain\Dictionary\Services;

use App\Domain\Dictionary\Models\Lexeme;
use App\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DictionaryCache
{
    private const LANGUAGES_KEY = 'dictionary:languages:active';

    private const LEXEME_KEY = 'dictionary:lexeme:%d:%s';

    private const LEXEME_TARGETS_KEY = 'dictionary:lexeme:%d:targets';

    /**
     * @return Collection<int, Language>
     */
    public function activeLanguages(): Collection
    {
        return Cache::remember(self::LANGUAGES_KEY, now()->addHour(), function () {
            return Language::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get();
        });
    }

    public function forgetLanguages(): void
    {
        Cache::forget(self::LANGUAGES_KEY);
    }

    public function rememberPublishedLexeme(int $id, ?string $targetLanguageCode, callable $callback): mixed
    {
        $target = $this->normalizeTarget($targetLanguageCode);
        $key = sprintf(self::LEXEME_KEY, $id, $target);

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $value = $callback();

        // Never cache misses — publish would otherwise stick a 404 for 30 minutes.
        if ($value === null) {
            return null;
        }

        Cache::put($key, $value, now()->addMinutes(30));
        $this->rememberTargetSuffix($id, $target);

        return $value;
    }

    public function forgetLexeme(Lexeme $lexeme): void
    {
        $targetsKey = sprintf(self::LEXEME_TARGETS_KEY, $lexeme->id);
        /** @var list<string> $targets */
        $targets = Cache::get($targetsKey, ['all']);

        foreach (array_unique([...$targets, 'all']) as $target) {
            Cache::forget(sprintf(self::LEXEME_KEY, $lexeme->id, $target));
        }

        Cache::forget($targetsKey);

        // Also clear known active language codes in case the target set was lost.
        foreach ($this->activeLanguages() as $language) {
            Cache::forget(sprintf(self::LEXEME_KEY, $lexeme->id, $language->code));
        }
    }

    private function normalizeTarget(?string $targetLanguageCode): string
    {
        if ($targetLanguageCode === null || $targetLanguageCode === '') {
            return 'all';
        }

        return strtolower(trim($targetLanguageCode));
    }

    private function rememberTargetSuffix(int $id, string $target): void
    {
        $targetsKey = sprintf(self::LEXEME_TARGETS_KEY, $id);
        /** @var list<string> $targets */
        $targets = Cache::get($targetsKey, []);
        $targets[] = $target;
        Cache::put($targetsKey, array_values(array_unique($targets)), now()->addMinutes(30));
    }
}
