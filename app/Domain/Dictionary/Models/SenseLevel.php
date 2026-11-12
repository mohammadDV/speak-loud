<?php

namespace App\Domain\Dictionary\Models;

use App\Models\User;
use Database\Factories\Dictionary\SenseLevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenseLevel extends Model
{
    /** @use HasFactory<SenseLevelFactory> */
    use HasFactory;

    protected $fillable = [
        'sense_id',
        'difficulty_level_id',
        'is_primary',
        'confidence',
        'source',
        'reviewed_by',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'confidence' => 'integer',
            'assessed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SenseLevelFactory
    {
        return SenseLevelFactory::new();
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
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
