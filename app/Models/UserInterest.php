<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInterest extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = ['user_id', 'interest_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }
}
