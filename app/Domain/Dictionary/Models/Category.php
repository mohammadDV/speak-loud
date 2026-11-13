<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Services\DictionaryCache;
use Database\Factories\Dictionary\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'display_name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    protected static function booted(): void
    {
        static::saved(function (Category $category): void {
            $cache = app(DictionaryCache::class);
            $cache->forgetCategories();

            if ($category->wasRecentlyCreated) {
                return;
            }

            if ($category->wasChanged(['is_active', 'display_name', 'code', 'sort_order', 'description'])) {
                $category->lexemes()->each(fn (Lexeme $lexeme) => $cache->forgetLexeme($lexeme));
            }
        });

        static::deleting(function (Category $category): void {
            $cache = app(DictionaryCache::class);
            $category->lexemes()->each(fn (Lexeme $lexeme) => $cache->forgetLexeme($lexeme));
        });

        static::deleted(function (): void {
            app(DictionaryCache::class)->forgetCategories();
        });
    }

    /**
     * Normalize and assert every ID is an active category.
     *
     * @param  list<mixed>  $ids
     * @return list<int>
     */
    public static function resolveActiveIds(array $ids): array
    {
        $requested = [];

        foreach ($ids as $id) {
            if (is_int($id)) {
                if ($id > 0) {
                    $requested[] = $id;
                }

                continue;
            }

            if (is_string($id) && ctype_digit($id) && (int) $id > 0) {
                $requested[] = (int) $id;

                continue;
            }

            throw ValidationException::withMessages([
                'category_ids' => 'One or more categories are invalid or inactive.',
            ]);
        }

        $requested = array_values(array_unique($requested));

        if ($requested === []) {
            return [];
        }

        $activeIds = static::query()
            ->active()
            ->whereIn('id', $requested)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        sort($requested);
        $sortedActive = $activeIds;
        sort($sortedActive);

        if ($sortedActive !== $requested) {
            throw ValidationException::withMessages([
                'category_ids' => 'One or more categories are invalid or inactive.',
            ]);
        }

        return $activeIds;
    }

    /**
     * @return BelongsToMany<Lexeme, $this>
     */
    public function lexemes(): BelongsToMany
    {
        return $this->belongsToMany(Lexeme::class)->withTimestamps();
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
