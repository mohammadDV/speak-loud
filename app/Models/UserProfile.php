<?php

namespace App\Models;

use App\Support\SitemapBuilder;
use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'profile_slug',
        'display_name',
        'bio',
        'gender',
        'birthdate',
        'nationality',
        'country_code',
        'profile_image_path',
        'background_image_path',
        'is_available',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_private' => 'boolean',
            'birthdate' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => SitemapBuilder::forgetCache());
        static::deleted(fn () => SitemapBuilder::forgetCache());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profileImageUrl(): ?string
    {
        return StorageUrl::resolve($this->profile_image_path);
    }

    public function backgroundImageUrl(): ?string
    {
        return StorageUrl::resolve($this->background_image_path);
    }
}
