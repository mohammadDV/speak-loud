<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    use LogsModelChanges;

    protected $fillable = [
        'sender_id', 'receiver_id', 'schedule_id', 'type',
        'status', 'message', 'responded_at', 'expires_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => LookupCache::flushHomeOpenSlots());
        static::deleted(fn () => LookupCache::flushHomeOpenSlots());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
