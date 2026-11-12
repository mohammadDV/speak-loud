<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $allowed = [
            'uuid',
            'email',
            'email_verified_at',
            'role',
            'status',
            'banned_at',
            'ban_reason',
            'banned_by',
            'last_login_at',
            'terms_accepted_at',
            'terms_version',
        ];

        $payload = collect($data)->only($allowed)->all();
        $payload['uuid'] = $payload['uuid'] ?? Str::uuid()->toString();

        if (isset($payload['role']) && ! in_array($payload['role'], ['user', 'admin', 'moderator', 'editor', 'reviewer'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Invalid role.',
            ]);
        }

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make((string) $data['password']);
        }

        $user = new User;
        $user->fill($payload);

        // Role/status are guarded from mass assignment by design.
        $user->forceFill([
            'role' => $payload['role'] ?? 'user',
            'status' => $payload['status'] ?? 'active',
            'banned_at' => $payload['banned_at'] ?? null,
            'ban_reason' => $payload['ban_reason'] ?? null,
            'banned_by' => $payload['banned_by'] ?? null,
        ]);
        $user->save();

        return $user;
    }
}
