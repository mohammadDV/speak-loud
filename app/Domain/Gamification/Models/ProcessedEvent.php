<?php

namespace App\Domain\Gamification\Models;

use App\Models\User;
use Database\Factories\Gamification\ProcessedEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $activity_type
 * @property string $source_id
 * @property string|null $mode
 * @property int $total_questions
 * @property int $correct_answers
 * @property bool $is_perfect
 * @property int $xp_total
 * @property Carbon $local_date
 * @property array<string, mixed>|null $summary
 * @property Carbon $processed_at
 */
class ProcessedEvent extends Model
{
    /** @use HasFactory<ProcessedEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'gamification_processed_events';

    protected $fillable = [
        'user_id',
        'activity_type',
        'source_id',
        'mode',
        'total_questions',
        'correct_answers',
        'is_perfect',
        'xp_total',
        'local_date',
        'summary',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'is_perfect' => 'boolean',
            'xp_total' => 'integer',
            'local_date' => 'date',
            'summary' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ProcessedEventFactory
    {
        return ProcessedEventFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
