<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use LogsModelChanges;

    const UPDATED_AT = null;

    protected $fillable = [
        'type', 'schedule_id', 'user_a_id', 'user_b_id', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function isScheduleGroup(): bool
    {
        return $this->type === 'schedule_group';
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function userA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('created_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function userCanAccess(int $userId): bool
    {
        if ($this->isDirect()) {
            return in_array($userId, [$this->user_a_id, $this->user_b_id], true);
        }

        return $this->participants()->where('users.id', $userId)->exists();
    }
}
