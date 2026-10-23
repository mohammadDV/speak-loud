<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    use LogsModelChanges;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::saved(fn () => LookupCache::flush());
        static::deleted(fn () => LookupCache::flush());
    }

    protected $fillable = ['name', 'slug', 'sort_order'];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'category_id');
    }
}
