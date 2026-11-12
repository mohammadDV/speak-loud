<?php

namespace App\Domain\Dictionary\Models;

use App\Domain\Dictionary\Enums\FormType;
use Database\Factories\Dictionary\LexemeFormFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property FormType $form_type
 * @property string $form
 * @property string $normalized_form
 * @property bool $is_primary
 */
class LexemeForm extends Model
{
    /** @use HasFactory<LexemeFormFactory> */
    use HasFactory;

    protected $fillable = [
        'lexeme_id',
        'form',
        'normalized_form',
        'form_type',
        'grammatical_features',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'form_type' => FormType::class,
            'grammatical_features' => 'array',
            'is_primary' => 'boolean',
        ];
    }

    protected static function newFactory(): LexemeFormFactory
    {
        return LexemeFormFactory::new();
    }

    /**
     * @return BelongsTo<Lexeme, $this>
     */
    public function lexeme(): BelongsTo
    {
        return $this->belongsTo(Lexeme::class);
    }
}
