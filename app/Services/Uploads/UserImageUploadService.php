<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;

class UserImageUploadService
{
    public function __construct(private S3UploadService $s3Uploads) {}

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
        $filename = $this->s3Uploads->makeFilename($file);

        $path = $this->s3Uploads->storeUploadedFile($file, $directory, $filename);

        if ($previousPath) {
            $this->deleteIfExists($previousPath);
        }

        return $path;
    }

    public function deleteIfExists(string $path): void
    {
        $this->s3Uploads->deleteIfExists($path);
    }
}

