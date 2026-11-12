<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\PhraseType;
use Database\Factories\Dictionary\CollocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ContentStatus $status
 * @property PhraseType $phrase_type
 * @property string $phrase
 */
class Collocation extends Model
{
    /** @use HasFactory<CollocationFactory> */
    use HasFactory;

    protected $fillable = [
        'sense_id',
        'phrase',
        'pattern_type',
        'phrase_type',
        'explanation',
        'frequency_label',
        'difficulty_level_id',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'phrase_type' => PhraseType::class,
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): CollocationFactory
    {
        return CollocationFactory::new();
    }

    /**
     * @return BelongsTo<Sense, $this>
     */
    public function sense(): BelongsTo
    {
        return $this->belongsTo(Sense::class);
    }

    /**
     * @return BelongsTo<DifficultyLevel, $this>
     */
    public function difficultyLevel(): BelongsTo
    {
        return $this->belongsTo(DifficultyLevel::class);
    }

    /**
     * @return HasMany<CollocationTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CollocationTranslation::class);
    }
}
