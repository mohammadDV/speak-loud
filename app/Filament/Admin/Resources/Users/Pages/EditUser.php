<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $allowed = [
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

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make((string) $data['password']);
        }

        if (isset($payload['role']) && ! in_array($payload['role'], ['user', 'admin', 'moderator', 'editor', 'reviewer'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Invalid role.',
            ]);
        }

        // role/status are intentionally not $fillable; admins may set them via allowlist only.
        $record->forceFill($payload);
        $record->save();

        return $record;
    }
}
