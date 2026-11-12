<?php

namespace App\Models;

use App\Domain\Dictionary\Services\DictionaryCache;
use App\Support\LogsModelChanges;
use App\Support\LookupCache;
use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory, LogsModelChanges;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::saved(function (): void {
            LookupCache::flush();
            app(DictionaryCache::class)->forgetLanguages();
        });
        static::deleted(function (): void {
            LookupCache::flush();
            app(DictionaryCache::class)->forgetLanguages();
        });
    }

    protected $fillable = [
        'code',
        'name_en',
        'name_native',
        'script',
        'direction',
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
