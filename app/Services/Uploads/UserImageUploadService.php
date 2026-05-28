<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserImageUploadService
{
    public function uploadProfileImage(UploadedFile $file, ?string $previousPath = null): string
    {
        return $this->uploadImage($file, 'users/profile-images', $previousPath);
    }

    public function uploadBackgroundImage(UploadedFile $file, ?string $previousPath = null): string
    {
        return $this->uploadImage($file, 'users/background-images', $previousPath);
    }

    private function uploadImage(UploadedFile $file, string $directory, ?string $previousPath = null): string
    {
        $disk = Storage::disk('s3');

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg';
        $filename = Str::uuid()->toString().'.'.$safeExtension;

        $path = $disk->putFileAs($directory, $file, $filename, [
            'visibility' => 'public',
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);

        if (! $path) {
            throw new \RuntimeException('Upload failed.');
        }

        if ($previousPath) {
            $this->deleteIfExists($previousPath);
        }

        return $path;
    }

    public function deleteIfExists(string $path): void
    {
        $disk = Storage::disk('s3');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}

