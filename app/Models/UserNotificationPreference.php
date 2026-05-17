<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = ['user_id', 'notification_type', 'channel', 'is_enabled'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
