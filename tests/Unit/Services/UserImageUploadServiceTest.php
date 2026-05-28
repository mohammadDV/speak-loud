<?php

use App\Services\Uploads\UserImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('user image upload service uploads to s3 and deletes previous path', function () {
    Storage::fake('s3');

    $service = app(UserImageUploadService::class);

    $oldPath = 'users/profile-images/old.jpg';
    Storage::disk('s3')->put($oldPath, 'old');
    expect(Storage::disk('s3')->exists($oldPath))->toBeTrue();

    $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);
    $newPath = $service->uploadProfileImage($file, $oldPath);

    expect($newPath)->toStartWith('users/profile-images/')
        ->and(Storage::disk('s3')->exists($newPath))->toBeTrue()
        ->and(Storage::disk('s3')->exists($oldPath))->toBeFalse();
});

