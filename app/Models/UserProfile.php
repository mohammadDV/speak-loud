<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
            'is_private'   => 'boolean',
            'birthdate'    => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profileImageUrl(): ?string
    {
        if (! $this->profile_image_path) {
            return null;
        }

        if (str_starts_with($this->profile_image_path, 'http')) {
            return $this->profile_image_path;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        return $disk->url($this->profile_image_path);
    }

    public function backgroundImageUrl(): ?string
    {
        if (! $this->background_image_path) {
            return null;
        }

        if (str_starts_with($this->background_image_path, 'http')) {
            return $this->background_image_path;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        return $disk->url($this->background_image_path);
    }
}
