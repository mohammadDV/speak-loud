<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use LogsModelChanges;

    protected $fillable = ['category_id', 'question', 'answer', 'is_active', 'sort_order'];

    protected static function booted(): void
    {
        static::saved(fn () => LookupCache::flush());
        static::deleted(fn () => LookupCache::flush());
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'category_id');
    }
}
