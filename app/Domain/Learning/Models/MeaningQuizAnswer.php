<?php

namespace App\Domain\Learning\Models;

use App\Domain\Dictionary\Models\Sense;
use Database\Factories\Learning\MeaningQuizAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeaningQuizAnswer extends Model
{
    /** @use HasFactory<MeaningQuizAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'meaning_quiz_session_id',
        'sense_id',
        'lemma',
        'correct_meaning',
        'selected_meaning',
        'is_correct',
        'match_score',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'match_score' => 'integer',
            'position' => 'integer',
            'sense_id' => 'integer',
        ];
    }

    protected static function newFactory(): MeaningQuizAnswerFactory
    {
        return MeaningQuizAnswerFactory::new();
    }

    /**
     * @return BelongsTo<MeaningQuizSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(MeaningQuizSession::class, 'meaning_quiz_session_id');
    }

    /**
     * @return BelongsTo<Sense, $this>
     */
    public function sense(): BelongsTo
    {
        return $this->belongsTo(Sense::class);
    }
}
