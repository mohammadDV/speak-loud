<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\GrammaticalGender;
use Database\Factories\Dictionary\GermanNounDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property GermanArticle|null $article
 * @property GrammaticalGender|null $grammatical_gender
 * @property string|null $plural_form
 * @property string|null $grammar_notes
 */
class GermanNounDetail extends Model
{
    /** @use HasFactory<GermanNounDetailFactory> */
    use HasFactory;

    protected $fillable = [
        'sense_id',
        'grammatical_gender',
        'article',
        'plural_form',
        'grammar_notes',
    ];

    protected function casts(): array
    {
        return [
            'grammatical_gender' => GrammaticalGender::class,
            'article' => GermanArticle::class,
        ];
    }

    protected static function newFactory(): GermanNounDetailFactory
    {
        return GermanNounDetailFactory::new();
    }

    /**
     * @return BelongsTo<Sense, $this>
     */
    public function sense(): BelongsTo
    {
        return $this->belongsTo(Sense::class);
    }
}
