<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Models\Language;
use Database\Factories\Dictionary\ExampleTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExampleTranslation extends Model
{
    /** @use HasFactory<ExampleTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'example_id',
        'language_id',
        'translation',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
        ];
    }

    protected static function newFactory(): ExampleTranslationFactory
    {
        return ExampleTranslationFactory::new();
    }

    /**
     * @return BelongsTo<Example, $this>
     */
    public function example(): BelongsTo
    {
        return $this->belongsTo(Example::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
