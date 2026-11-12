<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\TranslationType;
use App\Models\Language;
use Database\Factories\Dictionary\SenseTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ContentStatus $status
 * @property TranslationType $translation_type
 * @property string $translation_text
 */
class SenseTranslation extends Model
{
    /** @use HasFactory<SenseTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'sense_id',
        'target_language_id',
        'translation_text',
        'translation_type',
        'notes',
        'register',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'translation_type' => TranslationType::class,
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): SenseTranslationFactory
    {
        return SenseTranslationFactory::new();
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
    public function targetLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_language_id');
    }
}
