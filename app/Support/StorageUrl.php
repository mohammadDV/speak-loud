<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final class StorageUrl
{
    /**
     * Resolve a stored path to a public URL.
     *
     * Bundled public assets (e.g. images/blog/...) use asset(); uploaded
     * objects use the given disk (typically S3). Absolute http(s) URLs pass through.
     */
    public static function resolve(?string $path, string $disk = 's3'): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (self::isPublicAssetPath($path)) {
            return asset($path);
        }

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);

        return $filesystem->url($path);
    }

    private static function isPublicAssetPath(string $path): bool
    {
        if (str_starts_with($path, 'images/')) {
            return true;
        }

        return is_file(public_path($path));
    }
}
