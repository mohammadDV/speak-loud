<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Models\Language;
use App\Models\User;
use Database\Factories\Dictionary\LexemeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContentStatus $status
 * @property int $language_id
 * @property string $lemma
 * @property string $normalized_lemma
 * @property string|null $slug
 * @property string|null $pronunciation
 * @property string|null $disambiguation
 * @property-read Language|null $language
 */
class Lexeme extends Model
{
    /** @use HasFactory<LexemeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'language_id',
        'lemma',
        'normalized_lemma',
        'slug',
        'pronunciation',
        'disambiguation',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'language_id' => 'integer',
        ];
    }

    protected static function newFactory(): LexemeFactory
    {
        return LexemeFactory::new();
    }

    protected static function booted(): void
    {
        static::deleting(function (Lexeme $lexeme): void {
            if ($lexeme->isForceDeleting()) {
                return;
            }

            // Free the unique (language_id, slug) slot for reuse after soft delete.
            if (filled($lexeme->slug) && ! str_contains((string) $lexeme->slug, '-deleted-')) {
                $lexeme->slug = $lexeme->slug.'-deleted-'.$lexeme->id;
                $lexeme->saveQuietly();
            }
        });
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return HasMany<LexemeForm, $this>
     */
    public function forms(): HasMany
    {
        return $this->hasMany(LexemeForm::class);
    }

    /**
     * @return HasMany<Sense, $this>
     */
    public function senses(): HasMany
    {
        return $this->hasMany(Sense::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<Lexeme>  $query
     * @return Builder<Lexeme>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published);
    }
}
