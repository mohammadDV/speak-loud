<?php

namespace App\Domain\Dictionary\Services;

use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\Lexeme;
use App\Models\Language;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DictionaryCache
{
    private const LANGUAGES_KEY = 'dictionary:languages:active:v2';

    private const CATEGORIES_KEY = 'dictionary:categories:active:v2';

    private const LEXEME_KEY = 'dictionary:lexeme:%d:%s';

    private const LEXEME_TARGETS_KEY = 'dictionary:lexeme:%d:targets';

    /**
     * @return Collection<int, Language>
     */
    public function activeLanguages(): Collection
    {
        /** @var list<array<string, mixed>>|null $rows */
        $rows = Cache::get(self::LANGUAGES_KEY);

        if (! is_array($rows)) {
            $rows = Language::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get()
                ->map(fn (Language $language): array => $language->getAttributes())
                ->all();

            Cache::put(self::LANGUAGES_KEY, $rows, now()->addHour());
        }

        return (new Language)->newCollection(
            array_map(fn (array $attributes): Language => (new Language)->newFromBuilder($attributes), $rows)
        );
    }

    public function forgetLanguages(): void
    {
        Cache::forget(self::LANGUAGES_KEY);
        Cache::forget('dictionary:languages:active');
    }

    /**
     * @return Collection<int, Category>
     */
    public function activeCategories(): Collection
    {
        /** @var list<array<string, mixed>>|null $rows */
        $rows = Cache::get(self::CATEGORIES_KEY);

        if (! is_array($rows)) {
            $rows = Category::query()
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Category $category): array => $category->getAttributes())
                ->all();

            Cache::put(self::CATEGORIES_KEY, $rows, now()->addHour());
        }

        /** @var EloquentCollection<int, Category> $collection */
        $collection = (new Category)->newCollection(
            array_map(fn (array $attributes): Category => (new Category)->newFromBuilder($attributes), $rows)
        );

        return $collection;
    }

    public function forgetCategories(): void
    {
        Cache::forget(self::CATEGORIES_KEY);
        Cache::forget('dictionary:categories:active');
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
