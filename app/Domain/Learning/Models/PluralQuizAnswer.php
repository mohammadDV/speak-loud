<?php

namespace App\Domain\Learning\Models;

use Database\Factories\Learning\PluralQuizAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluralQuizAnswer extends Model
{
    /** @use HasFactory<PluralQuizAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'plural_quiz_session_id',
        'sense_id',
        'lemma',
        'correct_plural',
        'selected_plural',
        'is_correct',
        'match_score',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'sense_id' => 'integer',
            'is_correct' => 'boolean',
            'match_score' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function newFactory(): PluralQuizAnswerFactory
    {
        return PluralQuizAnswerFactory::new();
    }

    /**
     * @return BelongsTo<PluralQuizSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PluralQuizSession::class, 'plural_quiz_session_id');
    }
}
