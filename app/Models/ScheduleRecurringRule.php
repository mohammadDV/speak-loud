<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRecurringRule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'schedule_id', 'day_of_week', 'start_time', 'end_time',
        'valid_from', 'valid_until',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
