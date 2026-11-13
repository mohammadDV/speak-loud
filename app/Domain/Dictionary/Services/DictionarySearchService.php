<?php

namespace App\Domain\Dictionary\Services;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\Language;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DictionarySearchService
{
    public function __construct(
        private readonly DictionaryCache $cache,
    ) {}

    /**
     * @param  array{
     *     q: string,
     *     source_language: string,
     *     target_language?: string|null,
     *     level?: string|null,
     *     part_of_speech?: string|null,
     *     category?: string|null,
     *     per_page?: int
     * }  $params
     * @return LengthAwarePaginator<int, Lexeme>
     */
    public function search(array $params): LengthAwarePaginator
    {
        $query = trim($params['q']);
        $sourceLanguage = Language::query()
            ->where('code', $params['source_language'])
            ->where('is_active', true)
            ->firstOrFail();

        $normalized = TextNormalizer::normalize($query, $sourceLanguage->code);
        $targetLanguageId = null;

        if (! empty($params['target_language'])) {
            $targetLanguageId = Language::query()
                ->where('code', $params['target_language'])
                ->where('is_active', true)
                ->value('id');
        }

        $builder = Lexeme::query()
            ->published()
            ->where('language_id', $sourceLanguage->id)
            ->where(function (Builder $outer) use ($query, $normalized): void {
                $outer->where('lemma', $query)
                    ->orWhere('normalized_lemma', $normalized)
                    ->orWhere('normalized_lemma', 'like', $normalized.'%')
                    ->orWhereIn('id', function ($forms) use ($query, $normalized): void {
                        $forms->select('lexeme_id')
                            ->from('lexeme_forms')
                            ->where(function ($formQuery) use ($query, $normalized): void {
                                $formQuery->where('form', $query)
                                    ->orWhere('normalized_form', $normalized)
                                    ->orWhere('normalized_form', 'like', $normalized.'%');
                            });
                    });
            })
            ->with($this->publishedEagerLoads($targetLanguageId));

        if (! empty($params['part_of_speech'])) {
            $builder->whereHas('senses', function (Builder $senses) use ($params): void {
                $senses->published()
                    ->whereHas('partOfSpeech', fn (Builder $pos) => $pos->where('code', $params['part_of_speech']));
            });
        }

        if (! empty($params['level'])) {
            $builder->whereHas('senses', function (Builder $senses) use ($params): void {
                $senses->published()
                    ->whereHas('levels.difficultyLevel', fn (Builder $levels) => $levels->where('code', $params['level']));
            });
        }

        if (! empty($params['category'])) {
            $builder->whereHas(
                'categories',
                fn (Builder $categories) => $categories
                    ->where('code', $params['category'])
                    ->where('is_active', true),
            );
        }

        $builder->orderByRaw('CASE WHEN lemma = ? THEN 0 WHEN normalized_lemma = ? THEN 1 ELSE 2 END', [$query, $normalized])
            ->orderBy('lemma');

        return $builder->paginate(min(max((int) ($params['per_page'] ?? 15), 1), 50));
    }

    public function findPublishedLexeme(int $id, ?string $targetLanguageCode = null): ?Lexeme
    {
        $targetLanguageId = $targetLanguageCode
            ? Language::query()->where('code', $targetLanguageCode)->value('id')
            : null;

        /** @var Lexeme|null $lexeme */
        $lexeme = $this->cache->rememberPublishedLexeme($id, $targetLanguageCode, function () use ($id, $targetLanguageId) {
            return Lexeme::query()
                ->published()
                ->with($this->publishedEagerLoads($targetLanguageId))
                ->find($id);
        });

        return $lexeme;
    }

    /**
     * @return Collection<int, Sense>
     */
    public function publishedSensesForLexeme(Lexeme $lexeme, ?string $targetLanguageCode = null): Collection
    {
        $targetLanguageId = $targetLanguageCode
            ? Language::query()->where('code', $targetLanguageCode)->value('id')
            : null;

        return $lexeme->senses()
            ->published()
            ->with($this->senseEagerLoads($targetLanguageId))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return array<int|string, mixed>
     */
    private function publishedEagerLoads(?int $targetLanguageId): array
    {
        return [
            'language',
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('categories.sort_order'),
            'forms',
            'senses' => fn (HasMany $query) => $query
                ->where('status', ContentStatus::Published)
                ->orderBy('sort_order'),
            'senses.partOfSpeech',
            'senses.germanNounDetail',
            'senses.primaryLevel.difficultyLevel',
            'senses.translations' => function (HasMany $query) use ($targetLanguageId): void {
                $query->where('status', ContentStatus::Published)->orderBy('sort_order');
                if ($targetLanguageId !== null) {
                    $query->where('target_language_id', $targetLanguageId);
                }
            },
            'senses.examples' => fn (HasMany $query) => $query->where('status', ContentStatus::Published)->orderBy('sort_order'),
            'senses.examples.translations' => function (HasMany $query) use ($targetLanguageId): void {
                $query->where('status', ContentStatus::Published);
                if ($targetLanguageId !== null) {
                    $query->where('language_id', $targetLanguageId);
                }
            },
            'senses.collocations' => fn (HasMany $query) => $query->where('status', ContentStatus::Published)->orderBy('sort_order'),
            'senses.collocations.translations' => function (HasMany $query) use ($targetLanguageId): void {
                $query->where('status', ContentStatus::Published);
                if ($targetLanguageId !== null) {
                    $query->where('language_id', $targetLanguageId);
                }
            },
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function senseEagerLoads(?int $targetLanguageId): array
    {
        return [
            'partOfSpeech',
            'germanNounDetail',
            'primaryLevel.difficultyLevel',
            'translations' => function (HasMany $query) use ($targetLanguageId): void {
                $query->where('status', ContentStatus::Published)->orderBy('sort_order');
                if ($targetLanguageId !== null) {
                    $query->where('target_language_id', $targetLanguageId);
                }
            },
            'examples' => fn (HasMany $query) => $query->where('status', ContentStatus::Published)->orderBy('sort_order'),
            'examples.translations' => function (HasMany $query) use ($targetLanguageId): void {
                $query->where('status', ContentStatus::Published);
                if ($targetLanguageId !== null) {
                    $query->where('language_id', $targetLanguageId);
                }
            },
            'collocations' => fn (HasMany $query) => $query->where('status', ContentStatus::Published)->orderBy('sort_order'),
            'collocations.translations' => function (HasMany $query) use ($targetLanguageId): void {
                $query->where('status', ContentStatus::Published);
                if ($targetLanguageId !== null) {
                    $query->where('language_id', $targetLanguageId);
                }
            },
        ];
    }
}
