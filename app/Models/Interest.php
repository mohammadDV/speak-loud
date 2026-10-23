<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Interest extends Model
{
    use LogsModelChanges;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::saved(fn () => LookupCache::flush());
        static::deleted(fn () => LookupCache::flush());
    }

    protected $fillable = ['slug', 'name_en'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_interests');
    }
}
