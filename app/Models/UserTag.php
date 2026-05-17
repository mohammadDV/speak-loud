<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTag extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'tag'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
