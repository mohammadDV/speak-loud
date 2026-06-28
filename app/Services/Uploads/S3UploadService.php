<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class S3UploadService
{
    /**
     * @return array{visibility: string, CacheControl: string}
     */
    private function uploadOptions(): array
    {
        return [
            'visibility' => 'public',
            'CacheControl' => 'public, max-age=31536000, immutable',
        ];
    }

    public function storeUploadedFile(UploadedFile $file, string $directory, string $filename): string
    {
        $disk = Storage::disk('s3');

        $path = $disk->putFileAs($directory, $file, $filename, $this->uploadOptions());

        if (! $path) {
            throw new \RuntimeException('Upload to S3 failed.');
        }

        return $path;
    }

    public function storeTemporaryFile(TemporaryUploadedFile $file, ?string $directory, string $filename): string
    {
        return $this->storeUploadedFile($file, $directory ?? '', $filename);
    }

    public function makeFilename(UploadedFile $file, bool $preserveOriginalName = false): string
    {
        if ($preserveOriginalName) {
            return $file->getClientOriginalName();
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'svg'], true)
            ? $extension
            : 'bin';

        return Str::ulid()->toString().'.'.$safeExtension;
    }

    public function deleteIfExists(string $path): void
    {
        $disk = Storage::disk('s3');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
