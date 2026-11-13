<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Models\Language;
use Database\Factories\Dictionary\SenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContentStatus $status
 * @property int $lexeme_id
 * @property int|null $part_of_speech_id
 * @property string|null $definition
 * @property string|null $import_key
 * @property-read Lexeme|null $lexeme
 * @property-read PartOfSpeech|null $partOfSpeech
 * @property-read GermanNounDetail|null $germanNounDetail
 */
class Sense extends Model
{
    /** @use HasFactory<SenseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lexeme_id',
        'import_key',
        'part_of_speech_id',
        'definition',
        'definition_language_id',
        'usage_label',
        'domain',
        'frequency_rank',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'frequency_rank' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): SenseFactory
    {
        return SenseFactory::new();
    }

    /**
     * @return BelongsTo<Lexeme, $this>
     */
    public function lexeme(): BelongsTo
    {
        return $this->belongsTo(Lexeme::class);
    }

    /**
     * @return BelongsTo<PartOfSpeech, $this>
     */
    public function partOfSpeech(): BelongsTo
    {
        return $this->belongsTo(PartOfSpeech::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function definitionLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'definition_language_id');
    }

    /**
     * @return HasMany<SenseTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(SenseTranslation::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Example, $this>
     */
    public function examples(): HasMany
    {
        return $this->hasMany(Example::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Collocation, $this>
     */
    public function collocations(): HasMany
    {
        return $this->hasMany(Collocation::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<SenseLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(SenseLevel::class);
    }

    /**
     * @return HasOne<SenseLevel, $this>
     */
    public function primaryLevel(): HasOne
    {
        return $this->hasOne(SenseLevel::class)->where('is_primary', true);
    }

    /**
     * @return HasOne<GermanNounDetail, $this>
     */
    public function germanNounDetail(): HasOne
    {
        return $this->hasOne(GermanNounDetail::class);
    }

    /**
     * @param  Builder<Sense>  $query
     * @return Builder<Sense>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published);
    }
}
