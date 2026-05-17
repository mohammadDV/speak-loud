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
}
