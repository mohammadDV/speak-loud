<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Models\Language;
use Database\Factories\Dictionary\ExampleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ContentStatus $status
 * @property string $sentence
 */
class Example extends Model
{
    /** @use HasFactory<ExampleFactory> */
    use HasFactory;

    protected $fillable = [
        'sense_id',
        'language_id',
        'sentence',
        'usage_note',
        'difficulty_level_id',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): ExampleFactory
    {
        return ExampleFactory::new();
    }

    /**
     * @return BelongsTo<Sense, $this>
     */
    public function sense(): BelongsTo
    {
        return $this->belongsTo(Sense::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsTo<DifficultyLevel, $this>
     */
    public function difficultyLevel(): BelongsTo
    {
        return $this->belongsTo(DifficultyLevel::class);
    }

    /**
     * @return HasMany<ExampleTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ExampleTranslation::class);
    }
}
