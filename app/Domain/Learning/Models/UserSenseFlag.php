<?php

namespace App\Domain\Learning\Models;

use App\Domain\Dictionary\Models\Sense;
use App\Models\User;
use Database\Factories\Learning\UserSenseFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSenseFlag extends Model
{
    /** @use HasFactory<UserSenseFlagFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sense_id',
        'is_important',
        'is_learned',
    ];

    protected function casts(): array
    {
        return [
            'is_important' => 'boolean',
            'is_learned' => 'boolean',
            'sense_id' => 'integer',
        ];
    }

    protected static function newFactory(): UserSenseFlagFactory
    {
        return UserSenseFlagFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Sense, $this>
     */
    public function sense(): BelongsTo
    {
        return $this->belongsTo(Sense::class);
    }
}
