<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory, LogsModelChanges;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::saved(fn () => LookupCache::flush());
        static::deleted(fn () => LookupCache::flush());
    }

    protected $fillable = [
        'code',
        'name_en',
        'name_native',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function userLanguages(): HasMany
    {
        return $this->hasMany(UserLanguage::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
