<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Models\Language;
use Database\Factories\Dictionary\CollocationTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollocationTranslation extends Model
{
    /** @use HasFactory<CollocationTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'collocation_id',
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

    protected static function newFactory(): CollocationTranslationFactory
    {
        return CollocationTranslationFactory::new();
    }

    /**
     * @return BelongsTo<Collocation, $this>
     */
    public function collocation(): BelongsTo
    {
        return $this->belongsTo(Collocation::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
