<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description', 'type', 'language_id',
        'max_participants', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function recurringRule(): HasOne
    {
        return $this->hasOne(ScheduleRecurringRule::class);
    }

    public function oneTimeSlot(): HasOne
    {
        return $this->hasOne(ScheduleOneTimeSlot::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function groupConversation(): HasOne
    {
        return $this->hasOne(Conversation::class)->where('type', 'schedule_group');
    }

    public function isHost(int $userId): bool
    {
        return (int) $this->user_id === $userId;
    }

    public function hasAcceptedMember(int $userId): bool
    {
        return $this->claims()
            ->where('sender_id', $userId)
            ->where('status', 'accepted')
            ->exists();
    }

    public function userCanView(int $userId): bool
    {
        if ($this->isHost($userId) || $this->hasAcceptedMember($userId)) {
            return true;
        }

        return $this->status === 'active';
    }

    public function userCanAccessGroupChat(int $userId): bool
    {
        return $this->isHost($userId) || $this->hasAcceptedMember($userId);
    }
}

